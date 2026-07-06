<?php

declare(strict_types=1);

namespace App\Services;

final class LandingPageConfigSanitizer
{
    public function __construct(
        private readonly LandingPageHtmlSanitizer $htmlSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function sanitize(array $config): array
    {
        $blocks = [];

        foreach ($config['blocks'] ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;
            $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];

            if ($type === 'about' && isset($settings['body']) && is_string($settings['body'])) {
                $settings['body'] = $this->htmlSanitizer->sanitize($settings['body']);
            }

            $blocks[] = [
                'type' => $type,
                'visible' => array_key_exists('visible', $block)
                    ? (bool) $block['visible']
                    : true,
                'settings' => $settings,
            ];
        }

        $tickets = is_array($config['tickets'] ?? null) ? $config['tickets'] : [];

        return [
            'blocks' => $blocks,
            'tickets' => [
                'visible' => array_key_exists('visible', $tickets) ? (bool) $tickets['visible'] : true,
                'title' => isset($tickets['title']) && is_string($tickets['title']) && $tickets['title'] !== ''
                    ? $tickets['title']
                    : 'Get your tickets',
                'position' => isset($tickets['position']) && $tickets['position'] === 'hidden'
                    ? 'hidden'
                    : 'after_blocks',
            ],
        ];
    }
}
