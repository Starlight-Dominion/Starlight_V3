<?php

declare(strict_types=1);

namespace sdo\Dto\Bank;

use sdo\Infrastructure\BaseDto;

class BankTransactionRequest extends BaseDto
{
    public int $amount;

    protected function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1'
        ];
    }
}
