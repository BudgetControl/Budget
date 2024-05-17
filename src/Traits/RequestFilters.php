<?php
namespace Budgetcontrol\Budget\Traits;

use Psr\Http\Message\ServerRequestInterface as Request;

trait RequestFilters {
    

    public function extractFilters(Request $request, string $param = 'filterBy'): array
    {
        $filters = [];
        $queryParams = $request->getQueryParams();
        if (isset($queryParams[$param])) {
            foreach($queryParams[$param] as $key => $value) {
                if(in_array($key, $this->filters)) {
                    $filters = array_merge($filters, [$key => $value]);
                }
            }
        }

        return $filters;
    }
}