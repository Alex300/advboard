<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=sitemap.main
[END_COT_EXT]
==================== */
/**
 * Ads board module for Cotonti Siena
 *     Site map
 * @package Advert
 * @subpackage Site map
 * @author Kalnov Alexey    <kalnovalexey@yandex.ru>
 * @copyright (c) Portal30 Studio http://portal30.ru
 */
defined('COT_CODE') or die('Wrong URL');

global $structure;

// Self requirements
require_once cot_incfile('advert', 'module');

//require_once cot_incfile('comments', 'plug');

// Главная доски
sitemap_parse($t, $items, array(
    'url'  => cot_url('advert'),
    'date' => '', // omit
    'freq' => cot::$cfg['plugin']['sitemap']['freq'],
    'prio' => cot::$cfg['plugin']['sitemap']['prio']
));

// Страницы категорий
// Page categories
$auth_cache = array();

$category_list = $structure['advert'];

/* === Hook === */
foreach (cot_getextplugins('sitemap.advert.categorylist') as $pl) {
    include $pl;
}
/* ===== */

foreach ($category_list as $c => $cat) {
    $auth_cache[$c] = cot_auth('advert', $c, 'R');
    if (!$auth_cache[$c] || $c === 'system') continue;
    // Pagination support
    $maxrowsperpage = (cot::$cfg['advert']['cat_' . $c]['maxrowsperpage']) ? cot::$cfg['advert']['cat_' . $c]['maxrowsperpage'] :
        cot::$cfg['advert']['cat___default']['maxrowsperpage'];
    $count = advert_model_Advert::count(array(
        array('category', $cat),
        array('state', advert_model_Advert::PUBLISHED),
        array('begin', cot::$sys['now'], '<='),
        array('SQL', "expire = 0 OR expire > ".cot::$sys['now'])
    ));
    $subs = floor($count / $maxrowsperpage) + 1;
    foreach (range(1, $subs) as $pg) {
        $d = cot::$cfg['easypagenav'] ? $pg : ($pg - 1) * $maxrowsperpage;
        $urlp = $pg > 1 ? "c=$c&d=$d" : "c=$c";
        sitemap_parse($t, $items, array(
            'url'  => cot_url('advert', $urlp),
            'date' => '', // omit
            'freq' => cot::$cfg['plugin']['sitemap']['freq'],
            'prio' => cot::$cfg['plugin']['sitemap']['prio']
        ));
    }
}
// Объявления
$sitemap_where = array();
$sitemap_where['state'] = array('state', advert_model_Advert::PUBLISHED);
$sitemap_where['begin'] = array('begin', cot::$sys['now'], '<=');
$sitemap_where['expire'] = array('SQL', "expire = 0 OR expire > ".cot::$sys['now']);

/* === Hook === */
foreach (cot_getextplugins('sitemap.advert.query') as $pl) {
    include $pl;
}
/* ===== */

$condition = array();
foreach($sitemap_where as $key => $val) {
    $condition[] = $val;
}
$order = array(array('sort', 'DESC'));

$cnt = advert_model_Advert::count($condition);
if($cnt > 0) {
    $i = 0;
    while($i <= $cnt) {
        $advertisement = advert_model_Advert::find($condition, 20, $i, $order);
        if(!$advertisement) break;
        foreach($advertisement as $advert) {
            $i++;
            if (!$auth_cache[$advert->category]) continue;
            sitemap_parse($t, $items, array(
                'url'  => $advert->url,
                'date' => !empty($advert->updated) ? strtotime($advert->updated) : '',
                'freq' => cot::$cfg['plugin']['sitemap']['freq'],
                'prio' => cot::$cfg['plugin']['sitemap']['prio']
            ));
            unset($advert);
        }
        unset($advertisement);
    }
}