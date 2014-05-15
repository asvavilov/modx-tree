<?php
/**
 * Author: asvavilov
 * Product Name: tree
 * Product Version: 20140514
 * Product Description: tree resources for fast build menu
**/

/*
example:
[[tree
    &startId=`0`
    &tplWrapper=`tree_menu`
    &tplNode=`tree_node`
    &hideSubMenus=`1`
    &excludeDocs=`8`
    &level=`2`
]]
in chunk menu:
tree.nodes - menu nodes
in chunk node:
tree.node - resource
tree.node.is_active - parent or current
TODO: tree.node.tv.<TVNAME> - tvs values
tree.node.level - level of node
tree.node.first - first child?
tree.node.last - last child?
*/
// TODO учет контекстов в выборках
// TODO оптимизация: не читать скрытые узлы
// TODO вынести в параметры чтение tv

$out = '';

$startId = (int) $modx->getOption('startId', $scriptProperties);
$level = (int) $modx->getOption('level', $scriptProperties, 10);
$excludeDocs = trim($modx->getOption('excludeDocs', $scriptProperties));
if (!empty($excludeDocs))
{
    $excludeDocs = explode(',', $excludeDocs);
}
$tpls = array(
    'tplWrapper' => $modx->getOption('tplWrapper', $scriptProperties, '@INLINE <ul>[[+tree.nodes]]</ul>'),
    'tplNode' => $modx->getOption('tplNode', $scriptProperties, '@INLINE <li><a href="[[+tree.node.uri]]">[[+tree.node.pagetitle]]</a>[[+tree.node.childs]]</li>'),
);
$hideSubMenus = (bool) $modx->getOption('hideSubMenus', $scriptProperties);

$id = $modx->resource->get('id');

//$treeArray = $modx->getTree($startId);print_r($treeArray);
// TODO можно еще оптимизировать, если не требуется читать скрытые ветки (hideSubMenus)
$parent_ids = $modx->getParentIds($id);
//print_r($parent_ids);
$child_ids = $modx->getChildIds($startId, $level);
//$child_ids[] = $startId;
if (!empty($excludeDocs))
{
    $child_ids = array_diff($child_ids, $excludeDocs);
}
$res_criteria = array(
    'deleted' => '0',
    'published' => '1',
    'hidemenu' => '0',
);
$res_criteria = $modx->newQuery('modResource', $res_criteria);
$res_criteria->where(array('modResource.id:IN' => $child_ids));
$res_criteria->sortby('modResource.menuindex', 'ASC');
$ress = $modx->getCollection('modResource', $res_criteria);
$tree = array();
$ids = array();
foreach ($ress as $res)
{
    $tree[$res->get('parent')][$res->get('id')] = $res->toArray();
    $ids[$res->get('id')] = $res->get('id');
}
//print_r($tree);
//print_r($ids);
// FIXME оптимизация: tv не всегда и не все нужны, можно парсить шаблон и из него узнавать какие именно нужны
$tv_name = 'img';
$tv_criteria = array(
    'tmplvarid' => 3, // FIXME "img", читать по имени
    'contentid:IN' => $ids,
);
//$tv_criteria = $modx->newQuery('modTemplateVarResource', $tv_criteria);
$tvrs = $modx->getCollection('modTemplateVarResource', $tv_criteria);
$tvs = array();
foreach ($tvrs as $tvr)
{
    $tvs[$tvr->get('contentid')][$tv_name] = $tvr->get('value');
}
//print_r($tvs);

if (!class_exists('ProfiTree'))
{
    class ProfiTree
    {
        private $modx;
        private $tree = array();
        private $breadcrumb = array();
        private $tvs = array();
        private $tpls = array();
        private $hideSubMenus = false;
        function __construct(&$modx, $id, $tree_ids, $breadcrumb_ids, $tvs, $tpls, $hideSubMenus)
        {
            $this->modx = $modx;
            $this->id = $id;
            $this->tree = $tree_ids;
            $this->breadcrumb = $breadcrumb_ids;
            $this->tvs = $tvs;
            $this->tpls = $tpls;
            $this->hideSubMenus = $hideSubMenus;
        }
        function build($root_id, $level = 0)
        {
            $submenu = '';
            $count = count($this->tree[$root_id]);
            $pos = 1;
            foreach ($this->tree[$root_id] as $node_id => $node)
            {
                $is_active = (in_array($node_id, $this->breadcrumb) || $node_id == $this->id);
                $node['is_active'] = $is_active;
                if (!empty($this->tree[$node_id]) && (!$this->hideSubMenus || ($this->hideSubMenus && $is_active)))
                {
                    $node['childs'] = $this->build($node_id, $level + 1);
                }
                if (!empty($this->tvs[$node_id]))
                {
                    $node['tv'] = $this->tvs[$node_id];
                }
                $node['level'] = $level;
                if ($pos == 0)
                {
                    $node['first'] = true;
                }
                if ($pos == $count)
                {
                    $node['last'] = true;
                }
                $submenu .= $this->modx->getChunk($this->tpls['tplNode'],array(
                    'tree' => array(
                        'node' => $node,
                    ),
                ));
                $pos++;
            }
            $menu = $this->modx->getChunk($this->tpls['tplWrapper'], array(
                'tree' => array(
                    'nodes' => $submenu,
                ),
            ));
            return $menu;
        }
    }
}

$profi_tree = new ProfiTree($modx, $id, $tree, $parent_ids, $tvs, $tpls, $hideSubMenus);
$out .= $profi_tree->build($startId);

return $out;
