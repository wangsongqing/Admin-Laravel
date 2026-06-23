<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repository 基类：封装通用的数据访问样板。
 * 子类实现 modelClass() 返回对应 Model 的 FQCN。
 * 这是后端唯一允许直接操作 Eloquent / DB 的层。
 */
abstract class BaseRepository
{
    /**
     * 子类返回对应 Model 的全限定类名。
     */
    abstract protected function modelClass(): string;

    /**
     * 新建查询构造器。
     */
    protected function query(): Builder
    {
        $class = $this->modelClass();

        return $class::query();
    }

    /**
     * 按主键查单条，未命中返回 null。
     */
    public function findById(int $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    /**
     * 按条件查询单条，无结果返回 null。
     *
     * where 支持两种写法：
     *   - 等值：['status' => 1, 'type' => 'a']
     *   - 操作符：['age' => ['>', 18], 'name' => ['like', '%abc%']]
     */
    public function findOneBy(array $where, array $columns = ['*']): ?Model
    {
        return $this->applyWhere($this->query(), $where)->first($columns);
    }

    /**
     * 按条件查询多条（集合）。
     *
     * @param array $orderBy 可选排序：['id' => 'desc', 'created_at' => 'asc']
     * @return Collection<int, Model>
     */
    public function findManyBy(array $where, array $columns = ['*'], array $orderBy = []): Collection
    {
        $query = $this->applyWhere($this->query(), $where);

        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query->get($columns);
    }

    /**
     * 通用分页。
     *
     * @param int|null $page 显式页码（不依赖 request，便于测试）
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], ?int $page = null): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns, 'page', $page);
    }

    /**
     * 创建。
     */
    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    /**
     * 按主键更新。
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findById($id);
        if (!$model) {
            return false;
        }

        return $model->update($data);
    }

    /**
     * 按主键删除。
     */
    public function delete(int $id): bool
    {
        $model = $this->findById($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->delete();
    }

    /**
     * 把 where 数组应用到查询（支持等值 / [operator, value] 两种写法）。
     */
    protected function applyWhere(Builder $query, array $where): Builder
    {
        foreach ($where as $column => $condition) {
            if (is_array($condition)) {
                [$operator, $value] = $condition;
                $query->where($column, $operator, $value);
            } else {
                $query->where($column, '=', $condition);
            }
        }

        return $query;
    }
}
