<?php

namespace App\Services;

use App\Constants\PaginationConfig;
use App\DTOs\IndexProjectDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Project;


class ProjectService {

    public function getProjects (IndexProjectDTO $dto): LengthAwarePaginator | string {

        $query = Project::query();

        foreach ($dto->useFilters() as $filterKey => $filterValue) {
            $query->when($filterKey, fn($q) => $q->where($filterKey, "like", "%" . $filterValue . "%"));
        }

        $query->when($dto->search, fn($q) => $q->where("projectCode", "like", "%" . $dto->search . "%"));

        $projects = $query->paginate(PaginationConfig::PROJECTS_PER_PAGE);

        return $projects;

    }


}