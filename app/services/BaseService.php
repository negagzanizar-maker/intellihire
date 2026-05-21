<?php
abstract class BaseService
{
    protected BaseModel $model;

    public function findById(int $id): array|false  { return $this->model->findById($id); }
    public function getAll(array $f = []): array     { return $this->model->getAll($f); }
    public function create(array $data): int|bool    { return $this->model->create($data); }
    public function update(int $id, array $d): bool  { return $this->model->update($id, $d); }
    public function delete(int $id): bool            { return $this->model->delete($id); }
}
