<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Controller
{
    public function getPageSize()
    {
        if (request()->filled('per_page')) {
            return intval(request()->input('per_page'));
        }
        if (request()->filled('page_size')) {
            return intval(request()->input('page_size'));
        }
        if (request()->filled('rowsPerPage')) {
            return intval(request()->input('rowsPerPage'));
        }
        return config('controller.page_size', 20);
    }

    private function addOrSkipBaseTable(string $colName, string $tableBaseName): string
    {
        if (strpos($colName, '.') === false) {
            return $tableBaseName . '.' . $colName;
        }
        return $colName;
    }

    public function generateViewSetList(Request $request, Builder $querySet, array $filterBy, array $searchBy, array $orderBy, array $relationFields = [])
    {
        $tableBaseName = $querySet->getModel()->getTable();

        if ($request->hasAny($filterBy)) {
            foreach ($filterBy as $filter) {
                if ($request->filled($filter)) {
                    $querySet->where($this->addOrSkipBaseTable($filter, $tableBaseName), $request->input($filter));
                }
            }
        }

        if ($request->filled('search')) {
            $querySet->where(function ($q) use ($searchBy, $request, $tableBaseName, $relationFields) {
                $q->where(function ($q) use ($searchBy, $request, $tableBaseName) {
                    foreach ($searchBy as $searchByCol) {
                        $q->orWhere($this->addOrSkipBaseTable($searchByCol, $tableBaseName), 'like', '%' . $request->input('search') . '%');
                    }
                });

                foreach ($relationFields as $relationField) {
                    $relation = $relationField['relation'];
                    $fields = $relationField['fields'];

                    $q->orWhereHas($relation, function ($query) use ($request, $fields) {
                        $query->where(function ($query) use ($request, $fields) {
                            foreach ($fields as $field) {
                                $query->orWhere($field, 'like', '%' . $request->input('search') . '%');
                            }
                        });
                    });
                }

                return $q;
            });
        }

        if ($request->filled('order_by')) {
            $searchOrderList = explode(',', $request->input('order_by'));
            foreach ($searchOrderList as $searchOrderParam) {
                $searchOrderParamWithoutSign = preg_replace('/-/', '', $searchOrderParam, 1);
                $orderDirection = substr($searchOrderParam, 0, 1) === '-' ? 'desc' : 'asc';

                if (in_array($searchOrderParamWithoutSign, $orderBy, true)) {
                    $querySet->orderBy($this->addOrSkipBaseTable($searchOrderParamWithoutSign, $tableBaseName), $orderDirection);
                }
            }
        }

        $paginator = $this->getPageSize()
            ? $querySet->paginate($this->getPageSize())
            : $querySet->get();

        if ($this->getPageSize()) {
            return response()->json($paginator->toArray());
        }

        return response()->json(['data' => $paginator->toArray()]);
    }
}
