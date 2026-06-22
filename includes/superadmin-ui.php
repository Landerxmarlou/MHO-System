<?php
/**
 * Encode-style UI helpers for superadmin pages.
 */

function saPageOpen(string $class = ''): void
{
    $extra = $class !== '' ? ' ' . e($class) : '';
    echo '<div class="encode-report-page superadmin-page' . $extra . '">';
}

function saPageClose(): void
{
    echo '</div>';
}

function saBreadcrumb(array $items): void
{
    echo '<nav aria-label="breadcrumb" class="encode-breadcrumb">';
    echo '<ol class="breadcrumb mb-0">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $active = $i === $last;
        echo '<li class="breadcrumb-item' . ($active ? ' active' : '') . '"' . ($active ? ' aria-current="page"' : '') . '>';
        if (!$active && !empty($item['url'])) {
            $icon = !empty($item['icon']) ? '<i class="bi ' . e($item['icon']) . ' me-1"></i>' : '';
            echo '<a href="' . e($item['url']) . '">' . $icon . e($item['label']) . '</a>';
        } else {
            echo e($item['label']);
        }
        echo '</li>';
    }
    echo '</ol></nav>';
}

function saHeader(string $title, string $subtitle = '', string $badgesHtml = ''): void
{
    echo '<header class="encode-header mt-2">';
    echo '<div class="encode-header__main">';
    echo '<h1 class="encode-header__title">' . e($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p class="encode-header__subtitle">' . e($subtitle) . '</p>';
    }
    echo '</div>';
    if ($badgesHtml !== '') {
        echo '<div class="encode-header__badges">' . $badgesHtml . '</div>';
    }
    echo '</header>';
}

function saMetaGrid(array $items): void
{
    echo '<div class="encode-meta-grid">';
    foreach ($items as $item) {
        echo '<div class="encode-meta-item">';
        echo '<div class="encode-meta-item__icon"><i class="bi ' . e($item['icon']) . '"></i></div>';
        echo '<div>';
        echo '<div class="encode-meta-item__label">' . e($item['label']) . '</div>';
        echo '<div class="encode-meta-item__value">' . ($item['value_html'] ?? e($item['value'])) . '</div>';
        echo '</div></div>';
    }
    echo '</div>';
}

function saPanelOpen(string $title, array $options = []): void
{
    $count = $options['count'] ?? null;
    $actions = $options['actions'] ?? '';
    echo '<div class="sa-panel">';
    echo '<div class="sa-panel__header">';
    echo '<span class="sa-panel__title">' . e($title);
    if ($count !== null) {
        echo ' <span class="sa-panel__count">(' . (int) $count . ')</span>';
    }
    echo '</span>';
    if ($actions !== '') {
        echo '<div class="sa-panel__actions">' . $actions . '</div>';
    }
    echo '</div><div class="sa-panel__body">';
}

function saPanelClose(): void
{
    echo '</div></div>';
}

function saToolbarOpen(): void
{
    echo '<div class="encode-toolbar sa-toolbar">';
}

function saToolbarClose(): void
{
    echo '</div>';
}
