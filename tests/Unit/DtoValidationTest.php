<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Dto\Settings\UpdateIdentityRequest;
use sdo\Dto\Settings\UpdateCipherRequest;
use sdo\Exceptions\ValidationException;

class DtoValidationTest extends TestCase
{
    /**
     * @group identity
     */
    public function testUpdateIdentityRequestHydratesValidData(): void
    {
        $payload = [
            'username' => 'RobCommander123',
            'email' => 'commander@dominion.com'
        ];

        $dto = new UpdateIdentityRequest($payload);

        $this->assertSame('RobCommander123', $dto->username);
        $this->assertSame('commander@dominion.com', $dto->email);
    }

    /**
     * @group identity
     */
    public function testUpdateIdentityRequestFailsOnMissingFields(): void
    {
        $this->expectException(ValidationException::class);
        
        try {
            new UpdateIdentityRequest(['username' => 'test']);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            throw $e;
        }
    }

    /**
     * @group identity
     */
    public function testUpdateIdentityRequestFailsOnShortUsername(): void
    {
        $this->expectException(ValidationException::class);
        
        try {
            new UpdateIdentityRequest([
                'username' => 'ab',
                'email' => 'valid@email.com'
            ]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('username', $errors);
            $this->assertStringContainsString('at least 3 characters', $errors['username'][0]);
            throw $e;
        }
    }

    /**
     * @group identity
     */
    public function testUpdateIdentityRequestFailsOnSpecialCharacters(): void
    {
        $this->expectException(ValidationException::class);
        
        try {
            new UpdateIdentityRequest([
                'username' => 'Bad!Name',
                'email' => 'valid@email.com'
            ]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('username', $errors);
            $this->assertStringContainsString('letters and numbers', $errors['username'][0]);
            throw $e;
        }
    }

    /**
     * @group identity
     */
    public function testUpdateIdentityRequestFailsOnInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);
        
        try {
            new UpdateIdentityRequest([
                'username' => 'ValidName',
                'email' => 'not-an-email'
            ]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertStringContainsString('valid email address', $errors['email'][0]);
            throw $e;
        }
    }

    /**
     * @group cipher
     */
    public function testUpdateCipherRequestHydratesValidData(): void
    {
        $payload = [
            'current_password' => 'old_secret',
            'new_password' => 'new_secret_123',
            'confirm_password' => 'new_secret_123'
        ];

        $dto = new UpdateCipherRequest($payload);

        $this->assertSame('old_secret', $dto->current_password);
        $this->assertSame('new_secret_123', $dto->new_password);
        $this->assertSame('new_secret_123', $dto->confirm_password);
    }

    /**
     * @group cipher
     */
    public function testUpdateCipherRequestFailsOnShortPassword(): void
    {
        $this->expectException(ValidationException::class);
        
        try {
            new UpdateCipherRequest([
                'current_password' => 'old_secret',
                'new_password' => 'short',
                'confirm_password' => 'short'
            ]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('new_password', $errors);
            $this->assertStringContainsString('at least 8 characters', $errors['new_password'][0]);
            throw $e;
        }
    }

    /**
     * @group cipher
     */
    public function testUpdateCipherRequestFailsOnMissingCurrentPassword(): void
    {
        $this->expectException(ValidationException::class);
        
        try {
            new UpdateCipherRequest([
                'new_password' => 'strong_password',
                'confirm_password' => 'strong_password'
            ]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('current_password', $errors);
            throw $e;
        }
    }
}
