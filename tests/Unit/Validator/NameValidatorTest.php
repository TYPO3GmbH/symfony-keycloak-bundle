<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Tests\Unit\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use T3G\Bundle\Keycloak\Validator\Name;
use T3G\Bundle\Keycloak\Validator\NameValidator;

class NameValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NameValidator
    {
        return new NameValidator();
    }

    #[DataProvider('valuesDataProvider')]
    public function testNameValidator(?string $value, bool $isValid): void
    {
        $this->validator->validate($value, new Name());
        if (true === $isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('The value {{ value }} is not a valid name.')
                ->setParameter('{{ value }}', $value ?? 'null')
                ->assertRaised();
        }
    }

    public static function valuesDataProvider(): array
    {
        return [
            // Basic checks
            'null' => [null, true],
            'empty string' => ['', true],
            'Valid name' => ['Dscherêmy-Pasquàlle Gucci', true],
            'Valid name 2' => ['X Æ A-12', true],
            'Valid name 3' => ['all\' Arrabbiata', true],
            // Regex Characters
            'Smaller than' => ['You < Me', false],
            'Equals' => ['People = Shit', false],
            'Greater than' => ['Me > You', false],
            'Ampersand' => ['Simon & Garfunkel', false],
            'Quote' => ['Hans-Peter "HP"', false],
            'Opening square bracket' => ['[Harald', false],
            'Closing square bracket' => ['Sieglinde]', false],
            'Percent' => ['100% creative', false],
            'Exclamation mark' => ['My name\'s not Rick!', false],
            'Hash' => ['#Snoop', false],
            'Question mark' => ['Am I supposed to put my name in here?', false],
            'Paragraph' => ['§ 307 StGB', false],
            'Semicolon' => ['return true;', false],
            'Asterisk' => ['Ein *, der deinen Namen trägt.', false],
            'Tilde' => ['~~~oO Andiii Oo~~~', false],
            'Pipe' => ['Roddy |er', false],
            'Opening bracket' => ['(Herribert', false],
            'Closing bracket' => ['Gisela)', false],
            'Circumflex' => ['Ich heiße Marvin ^^', false],
            'Opening curly bracket' => ['{Hugo', false],
            'Closing curly bracket' => ['Jackqueline}', false],
            'FORM FEED' => ["Formi\f", false],
            'CARRIAGE RETURN' => ["\rPing", false],
            'TAB' => ["Tele\tie", false],
            'Vertical whitespace' => ["Whitespacei\n\x0B\f\r\x85\u2028\u2029", false],
            'Control character' => ["Shifty\x0E", false],
            'DEL character' => ["Delete\x7F", false],
            // General cases
            'Tags' => ['<evil-html><script>alert(\'Anyone reading this is stupid.\');</script></evil-html>', false],
            'Query Strings' => ['?exposeData=true&evilParameters[]=shutdown&evilParameters%5B%5D=encoded-shutdown', false],
            'SQL Queries' => ['DELETE FROM users;', false],
            'Example' => ['{{7*7}}\nevil.com', false],
        ];
    }
}
