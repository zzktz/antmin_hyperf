<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Antmin\Model\Menu;

class MenuRepository
{
    public function __construct(private readonly Menu $menuModel)
    {
    }

    public function add(array $info): int
    {
        return (int) $this->menuModel->create($info)->id;
    }

    public function edit(array $info, int $id): bool
    {
        return (bool) $this->menuModel->find($id)?->update($info);
    }

    public function del(int $id): bool
    {
        return (bool) $this->menuModel->find($id)?->delete();
    }

    public function getInfo(int $id): array
    {
        $one = $this->menuModel->find($id);
        return $one ? $one->toArray() : [];
    }

    public function getAllCacheData(): array
    {
        return $this->menuModel->newQuery()->orderBy('listorder')->orderByDesc('id')->get()->toArray();
    }

    public function getDataByParentId(int $parentId): array
    {
        $data = $this->menuModel->newQuery()
            ->where('parent_id', $parentId)
            ->orderBy('listorder')
            ->orderByDesc('id')
            ->get(['id', 'title'])
            ->toArray();

        return array_values($data);
    }
}
