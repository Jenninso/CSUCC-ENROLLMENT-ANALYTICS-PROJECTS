<?php

namespace Tests\Unit;

use App\Support\AccountCategory;
use Tests\TestCase;

class AccountCategoryTest extends TestCase
{
    public function test_recognizes_institutional_and_personal_emails(): void
    {
        config(['accounts.institutional_email_domains' => ['csucc.edu.ph']]);

        $this->assertSame(AccountCategory::INSTITUTIONAL, AccountCategory::fromEmail('jennel.paracuelles@csucc.edu.ph'));
        $this->assertSame(AccountCategory::INSTITUTIONAL, AccountCategory::fromEmail('staff@mail.csucc.edu.ph'));
        $this->assertSame(AccountCategory::PERSONAL, AccountCategory::fromEmail('person@gmail.com'));
        $this->assertSame('Institutional Account', AccountCategory::label('dean@csucc.edu.ph'));
        $this->assertSame('Personal Account', AccountCategory::label('dean@yahoo.com'));
    }
}
