<?php
namespace Ludelix\Fluid\Utilities;

use Ludelix\Interface\Fluid\UtilityInterface;

class Display implements UtilityInterface
{
    public const DISPLAY = [
        'none' => 'none',
        'flex' => 'flex',
        'block' => 'block',
        'inline' => 'inline',
        'inlineBlock' => 'inline-block',
        'inlineFlex' => 'inline-flex',
        'grid' => 'grid',
        'inlineGrid' => 'inline-grid',
        'table' => 'table',
        'tableRow' => 'table-row',
        'tableCell' => 'table-cell',
        'tableColumn' => 'table-column',
        'tableCaption' => 'table-caption',
        'tableRowGroup' => 'table-row-group',
        'tableHeaderGroup' => 'table-header-group',
        'tableFooterGroup' => 'table-footer-group',
        'tableColumnGroup' => 'table-column-group',
        'hidden' => 'none',
    ];

    public static function getStyles(): array
    {
        $styles = [];
        foreach (self::DISPLAY as $key => $value) {
            $styles["fl-$key"] = ['display' => $value];
        }
        return $styles;
    }
}