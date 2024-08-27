<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Http\Factories\HeaderFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use JustSteveKing\Tools\Http\Enums\Status;

final readonly class ModelResponse implements Responsable
{
    /**
     * @param JsonResource $data
     * @param Status $status
     */
    public function __construct(
        private JsonResource $data,
        private Status $status = Status::OK,
    ) {}

    /**
     * @param $request
     * @return JsonResponse
     */
    public function toResponse($request): JsonResponse
    {
        return new JsonResponse(
            data: $this->data,
            status: $this->status->value,
            headers: HeaderFactory::default(),
        );
    }
}
