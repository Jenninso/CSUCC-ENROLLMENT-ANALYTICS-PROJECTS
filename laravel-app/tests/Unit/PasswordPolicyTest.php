<?php

namespace Tests\Unit;

use App\Support\PasswordPolicy;
use PHPUnit\Framework\TestCase;

class PasswordPolicyTest extends TestCase
{
    public function test_classifies_weak_medium_and_strong_passwords(): void
    {
        $this->assertSame('weak', PasswordPolicy::level('abc'));
        $this->assertSame('weak', PasswordPolicy::level('password'));
        $this->assertSame('medium', PasswordPolicy::level('Password1'));
        $this->assertSame('strong', PasswordPolicy::level('Password1!ab'));
    }

    public function test_rejects_weak_and_incomplete_passwords(): void
    {
        $this->assertFalse(PasswordPolicy::isAcceptable('password'));
        $this->assertFalse(PasswordPolicy::isAcceptable('PASSWORD1'));
        $this->assertTrue(PasswordPolicy::isAcceptable('Password1'));
        $this->assertTrue(PasswordPolicy::isAcceptable('Password1!ab'));
    }
}
