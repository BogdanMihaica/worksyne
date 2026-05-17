<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiResourceController extends Controller
{
    protected string $modelClass;

    public function index(): JsonResponse
    {
        return response()->json($this->modelClass::query()->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $model = $this->modelClass::query()->create(
            $this->validatedAttributes($request, $this->storeRules())
        );

        return response()->json($model, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->findModel($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->findModel($id);

        $model->update(
            $this->validatedAttributes($request, $this->updateRules($model))
        );

        return response()->json($model);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->findModel($id)->delete();

        return response()->json(null, 204);
    }

    abstract protected function storeRules(): array;
    abstract protected function updateRules(Model $model): array;

    protected function validatedAttributes(Request $request, array $rules): array
    {
        return $request->validate($rules);
    }

    protected function findModel(int $id): Model
    {
        return $this->modelClass::query()->findOrFail($id);
    }

}
