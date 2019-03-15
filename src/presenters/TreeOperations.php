<?php

namespace Varhall\Restino\Presenters;


trait TreeOperations
{
    public function restList(array $data = [])
    {
        $result = parent::restList($data)->fetchAll();

        return $this->buildTree($result);
    }

    public function restUpdate($id, array $data)
    {
        $this->renumberTree($data['tree']);
        $this->saveTree($data['tree']);

        return $data;
    }

    protected function methodsOnly()
    {
        return [ 'list', 'update' ];
    }

    protected function validationDefinition()
    {
        return [
            'tree'              => [ 'array', 'required' ],
        ];
    }


    /// TREE CONFIG

    protected function treeColumns()
    {
        $class = $this->modelClass();

        return (object) array_merge(
            (array) $class::treeColumnsConfig(),
            [ 'id'  => $class::identifier() ]
        );
    }

    protected function defaultData()
    {
        return [];
    }

    protected function extendItem($item)
    {
        return [];
    }


    /// TREE OPERATIONS

    protected function buildTree($data, $left = 0, $right = NULL)
    {
        $tree = array();

        foreach ($data as $item) {
            $l = $item->{$this->treeColumns()->left};
            $r = $item->{$this->treeColumns()->right};

            if ($l === $left + 1 && (is_null($right) || $r < $right)) {
                $tree[] = array_merge($item->toArray(), $this->extendItem($item), [
                    'children'  => $this->buildTree(array_filter($data, function($x) use ($l, $r) { return $x->{$this->treeColumns()->left} > $l && $x->{$this->treeColumns()->right} < $r; }), $l, $r)
                ]);

                $left = $r;
            }
        }

        return $tree;
    }

    protected function renumberTree(&$data, $index = 1, $parent = NULL)
    {
        foreach ($data as &$item) {
            $item[$this->treeColumns()->left] = $index++;
            $item[$this->treeColumns()->parent] = $parent ? $parent[$this->treeColumns()->id] : NULL;

            $index = $this->renumberTree($item['children'], $index, $item);

            $item[$this->treeColumns()->right] = $index++;
        }

        return $index;
    }

    protected function flattenTree($data)
    {
        $result = [];

        foreach ($data as $item) {
            $result[] = $item;
            $result = array_merge($result, $this->flattenTree($item['children']));
        }

        return $result;
    }

    protected function saveTree($data)
    {
        $class = $this->modelClass();
        $cols = $this->treeColumns();

        $data = $this->flattenTree($data);
        $currents = $class::all()->fetchPairs($cols->id);

        foreach ($data as $item) {
            $values = [
                $cols->left     => $item[$cols->left],
                $cols->right    => $item[$cols->right],
                $cols->parent   => $item[$cols->parent],
            ];

            // update (exists in both the DB and in the array)
            if (isset($currents[$item[$cols->id]])) {
                if ($this->compareChanges($currents[$item[$cols->id]], $values)) {
                    $currents[$item[$cols->id]]->update($values);
                    $this->objectChanged($currents[$item[$cols->id]], $values);
                }

                unset($currents[$item[$cols->id]]);

                // create (does not exist in the DB)
            } else {
                $class::create(array_merge($this->defaultData(), $values));
            }
        }

        // delete all nodes which were removed
        foreach ($class::where($cols->id, array_keys($currents)) as $item) {
            $item->delete();
        }
    }

    protected function compareChanges($current, $values)
    {
        $cols = $this->treeColumns();

        foreach ([ $cols->left, $cols->right ] as $col) {
            if ($current->{$col} !== $values[$col])
                return TRUE;
        }

        return FALSE;
    }

    protected function objectChanged($current, $values)
    {

    }
}