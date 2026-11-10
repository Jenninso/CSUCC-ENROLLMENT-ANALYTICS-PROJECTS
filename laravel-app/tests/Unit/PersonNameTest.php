<?php

namespace Tests\Unit;

use App\Rules\PersonName;
use PHPUnit\Framework\TestCase;

class PersonNameTest extends TestCase
{
    public function test_accepts_properly_capitalized_names(): void
    {
        $this->assertNull(PersonName::errorFor('Jennel'));
        $this->assertNull(PersonName::errorFor('Dela Cruz'));
        $this->assertNull(PersonName::errorFor("O'Neil"));
        $this->assertNull(PersonName::errorFor('Maria-Clara'));
    }

    public function test_rejects_invalid_name_patterns(): void
    {
        $this->assertSame('Numbers are not allowed in the name.', PersonName::errorFor('Jennel2'));
        $this->assertSame('Double spacing is not allowed in the name.', PersonName::errorFor('Jennel  P'));
        $this->assertSame('The first letter of the name must be a capital letter.', PersonName::errorFor('jennel'));
        $this->assertSame(
            'After the first capital letter, use small letters only. Names like JoHn are not allowed.',
            PersonName::errorFor('JoHn')
        );
        $this->assertSame('Three consecutive letters of the same kind are not allowed.', PersonName::errorFor('Annna'));
        $this->assertSame('Three consecutive letters of the same kind are not allowed.', PersonName::errorFor('JENNNEL'));
    }
}
