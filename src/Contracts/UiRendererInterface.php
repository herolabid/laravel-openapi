<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Contracts;

/**
 * Interface for rendering OpenAPI UI (Swagger UI, ReDoc, etc).
 */
interface UiRendererInterface
{
    /**
     * Render the UI HTML.
     */
    public function render(string $specUrl): string;

    /**
     * Get UI assets (CSS, JS).
     *
     * @return array{css: array<string>, js: array<string>}
     */
    public function getAssets(): array;

    /**
     * Get UI name.
     */
    public function getName(): string;
}
