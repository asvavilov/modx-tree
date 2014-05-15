modx-tree
=========

optimized menu builder

minimum db-queries

---
Author: asvavilov

Product Name: tree

Product Version: 20140514

Product Description: tree resources for fast build menu

---
example:

[[tree
    &startId=\`0\`
    &tplWrapper=\`tree_menu\`
    &tplNode=\`tree_node\`
    &hideSubMenus=\`1\`
    &excludeDocs=\`8\`
    &level=\`2\`
]]

---
in chunk menu:

tree.nodes - menu nodes

---
in chunk node:

tree.node - resource

tree.node.is_active - parent or current

TODO: tree.node.tv.TVNAME - tvs values

tree.node.level - level of node

tree.node.first - first child?

tree.node.last - last child?

---
// TODO учет контекстов в выборках

// TODO оптимизация: не читать скрытые узлы

// TODO вынести в параметры чтение tv
