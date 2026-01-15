<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Infrastructure\Cache\DependencyTracker;

class DependencyTrackerTest extends TestCase
{
    private DependencyTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker = new DependencyTracker();
    }

    /** @test */
    public function it_tracks_dependencies(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php', 'FileC.php']);

        $deps = $this->tracker->getDependencies('FileA.php');

        $this->assertCount(2, $deps);
        $this->assertContains('FileB.php', $deps);
        $this->assertContains('FileC.php', $deps);
    }

    /** @test */
    public function it_gets_dependents(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);
        $this->tracker->track('FileC.php', ['FileB.php']);

        $dependents = $this->tracker->getDependents('FileB.php');

        $this->assertCount(2, $dependents);
        $this->assertContains('FileA.php', $dependents);
        $this->assertContains('FileC.php', $dependents);
    }

    /** @test */
    public function it_gets_affected_files(): void
    {
        // A depends on B, B depends on C
        $this->tracker->track('FileA.php', ['FileB.php']);
        $this->tracker->track('FileB.php', ['FileC.php']);

        // When C changes, A and B are affected
        $affected = $this->tracker->getAffectedFiles('FileC.php');

        $this->assertContains('FileC.php', $affected); // Itself
        $this->assertContains('FileB.php', $affected); // Direct dependent
        $this->assertContains('FileA.php', $affected); // Indirect dependent
    }

    /** @test */
    public function it_handles_circular_dependencies(): void
    {
        // A -> B -> C -> A (circular)
        $this->tracker->track('FileA.php', ['FileB.php']);
        $this->tracker->track('FileB.php', ['FileC.php']);
        $this->tracker->track('FileC.php', ['FileA.php']);

        $affected = $this->tracker->getAffectedFiles('FileA.php');

        // Should not infinite loop
        $this->assertIsArray($affected);
        $this->assertContains('FileA.php', $affected);
    }

    /** @test */
    public function it_checks_if_file_has_dependencies(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);

        $this->assertTrue($this->tracker->hasDependencies('FileA.php'));
        $this->assertFalse($this->tracker->hasDependencies('FileB.php'));
    }

    /** @test */
    public function it_checks_if_file_has_dependents(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);

        $this->assertTrue($this->tracker->hasDependents('FileB.php'));
        $this->assertFalse($this->tracker->hasDependents('FileA.php'));
    }

    /** @test */
    public function it_clears_all_dependencies(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);

        $this->tracker->clear();

        $this->assertEmpty($this->tracker->getAllFiles());
    }

    /** @test */
    public function it_removes_specific_file(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);
        $this->tracker->track('FileC.php', ['FileB.php']);

        $this->tracker->remove('FileA.php');

        $dependents = $this->tracker->getDependents('FileB.php');

        $this->assertCount(1, $dependents);
        $this->assertNotContains('FileA.php', $dependents);
        $this->assertContains('FileC.php', $dependents);
    }

    /** @test */
    public function it_gets_all_tracked_files(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);
        $this->tracker->track('FileC.php', ['FileD.php']);

        $files = $this->tracker->getAllFiles();

        $this->assertCount(4, $files);
    }

    /** @test */
    public function it_exports_and_imports_dependencies(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);
        $this->tracker->track('FileC.php', ['FileD.php']);

        $exported = $this->tracker->export();

        $newTracker = new DependencyTracker();
        $newTracker->import($exported);

        $this->assertEquals(
            $this->tracker->getDependencies('FileA.php'),
            $newTracker->getDependencies('FileA.php')
        );
    }

    /** @test */
    public function it_gets_stats(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);
        $this->tracker->track('FileC.php', ['FileB.php']);

        $stats = $this->tracker->getStats();

        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('files_with_dependencies', $stats);
        $this->assertArrayHasKey('files_with_dependents', $stats);
        $this->assertEquals(3, $stats['total_files']);
    }

    /** @test */
    public function it_gets_affected_files_for_many(): void
    {
        $this->tracker->track('FileA.php', ['FileB.php']);
        $this->tracker->track('FileC.php', ['FileD.php']);

        $affected = $this->tracker->getAffectedFilesForMany(['FileB.php', 'FileD.php']);

        $this->assertContains('FileA.php', $affected);
        $this->assertContains('FileC.php', $affected);
    }
}
