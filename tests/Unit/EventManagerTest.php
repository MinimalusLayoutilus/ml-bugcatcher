<?php

namespace mnhcc\ml\tests\Unit;

use PHPUnit\Framework\TestCase;
use mnhcc\ml\classes\EventManager;

class EventManagerTest extends TestCase
{
    // --- cleanEventName ---

    /**
     * @dataProvider provideCleanEventNameCases
     */
    public function testCleanEventName_stripsOnPrefixAndAppliesCase($input, $asKey, $expected)
    {
        $this->assertSame($expected, EventManager::cleanEventName($input, $asKey));
    }

    public function provideCleanEventNameCases()
    {
        return [
            'on prefix ucfirst'            => ['onClick',    false, 'Click'],
            'on prefix as key (lowercase)' => ['onClick',    true,  'click'],
            'onchange as key'              => ['onchange',   true,  'change'],
            'onSubmit ucfirst'             => ['onSubmit',   false, 'Submit'],
            'no on prefix ucfirst'         => ['change',     false, 'Change'],
            'no on prefix as key'          => ['change',     true,  'change'],
            'uppercase ON stripped'        => ['ONload',     false, 'Load'],
            'all lowercase result'         => ['onkeydown',  false, 'Keydown'],
            'as key all lowercase'         => ['onkeydown',  true,  'keydown'],
        ];
    }
}
