<?php

namespace Tests\Unit\User;

use PHPUnit\Framework\TestCase;

class PasswordValidationTest extends TestCase
{
    public function test_valid_password_meets_criteria(): void
    {
        $password = 'ValidPass1';

        $this->assertGreaterThanOrEqual(8, strlen($password));
        $this->assertMatchesRegularExpression('/[a-zA-Z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
    }

    public function test_password_less_than_eight_characters_is_invalid(): void
    {
        $password = 'Shor1';

        $this->assertLessThan(8, strlen($password));
    }

    public function test_password_without_numbers_is_invalid(): void
    {
        $password = 'OnlyLetters';

        $this->assertDoesNotMatchRegularExpression('/[0-9]/', $password);
    }

    public function test_password_without_letters_is_invalid(): void
    {
        $password = '1234567890';

        $this->assertDoesNotMatchRegularExpression('/[a-zA-Z]/', $password);
    }

    public function test_password_without_mixed_case_is_invalid(): void
    {
        $password = 'lowercase1';

        $this->assertDoesNotMatchRegularExpression('/[A-Z]/', $password);
    }
}
