<?php

namespace mnhcc\ml\tests\Unit;

use PHPUnit\Framework\TestCase;
use mnhcc\ml\classes\Error;

/**
 * Verifies that Error::renderError walks the previous-exception chain so the
 * BugCatcher debug overlay shows wrappers AND their root cause(s).
 */
class ErrorRenderTest extends TestCase
{
    public function testRenderError_singleExceptionEmitsOneFrame()
    {
        $exc = new \LogicException('plain failure', 42);

        $output = $this->_invokeRenderError($exc, 'hash-1');

        // One primary frame, no caused-by.
        $this->assertSame(1, substr_count($output, 'class="dbgHeader'));
        $this->assertNotContains('caused-by', $output);
        $this->assertNotContains('Caused by:', $output);

        // Class name + message + label appear.
        $this->assertContains('LogicException', $output);
        $this->assertContains('plain failure', $output);
        $this->assertContains('[hash-1]', $output);
    }

    public function testRenderError_chainedExceptionEmitsCauseFrame()
    {
        $cause   = new \RuntimeException('original cause', 12);
        $wrapper = new \LogicException('wrapper exception', 99, $cause);

        $output = $this->_invokeRenderError($wrapper, 'wrap-hash');

        // Two frames — primary + previous.
        $this->assertSame(2, substr_count($output, 'class="dbgHeader'));

        // Primary frame: no "caused-by" CSS class on its header.
        $primaryHeaderPos = strpos($output, '[wrap-hash]');
        $this->assertNotFalse($primaryHeaderPos, 'primary header label must appear');

        // Previous frame: header carries the caused-by CSS class AND the
        // "Caused by:" label inline.
        $this->assertContains('class="dbgHeader caused-by"', $output);
        $this->assertContains('Caused by:', $output);

        // Both classes present.
        $this->assertContains('LogicException', $output);
        $this->assertContains('RuntimeException', $output);
        $this->assertContains('wrapper exception', $output);
        $this->assertContains('original cause', $output);

        // Each frame has its own toggle container with a distinct id.
        if (preg_match_all('/dbgContainer_BugCatcher(\d+)/', $output, $matches)) {
            $ids = array_unique($matches[1]);
            $this->assertGreaterThanOrEqual(2, count($ids),
                'each frame must use a unique dbgContainer id');
        } else {
            $this->fail('expected at least one dbgContainer in the output');
        }
    }

    public function testRenderError_threeLevelChainEmitsThreeFrames()
    {
        $root    = new \RuntimeException('root', 1);
        $middle  = new \RuntimeException('middle', 2, $root);
        $top     = new \RuntimeException('top', 3, $middle);

        $output = $this->_invokeRenderError($top, 'h');

        $this->assertSame(3, substr_count($output, 'class="dbgHeader'));
        $this->assertSame(2, substr_count($output, 'caused-by'));
        $this->assertContains('top', $output);
        $this->assertContains('middle', $output);
        $this->assertContains('root', $output);
    }

    // --- report() / soft-exception registry ---

    public function testReport_alwaysLogsAndRespectsDebugFlag()
    {
        // DEBUG off: report() must log but NOT keep the exception in the
        // soft registry.  We verify by reading getSoftExceptions() empty
        // afterwards.  log() writes to error_log which we redirect to a
        // throw-away tempfile so the test stays self-contained.
        $logFile = tempnam(sys_get_temp_dir(), 'bugcatcher-log-');
        $orig    = ini_set('error_log', $logFile);

        $instance = $this->_freshErrorInstance();

        if (defined('DEBUG')) {
            $this->markTestSkipped('DEBUG already defined; cannot test off-state');
        }

        $instance->report(new \RuntimeException('soft failure off'));
        $this->assertSame([], $instance->getSoftExceptions(),
            'with DEBUG undefined, report() must NOT keep the exception');

        // The error_log file should now contain something — not asserting
        // the format, just that log() actually fired.
        $this->assertGreaterThan(0, filesize($logFile),
            'report() must call log() unconditionally');

        ini_set('error_log', $orig);
        @unlink($logFile);
    }

    public function testReport_keepsSoftExceptionsWhenDebugOn()
    {
        if (!defined('DEBUG')) {
            define('DEBUG', true);
        } elseif (!DEBUG) {
            $this->markTestSkipped('DEBUG defined but falsy — cannot run on-state test');
        }

        $logFile = tempnam(sys_get_temp_dir(), 'bugcatcher-log-');
        $orig    = ini_set('error_log', $logFile);

        $instance = $this->_freshErrorInstance();
        $exc1     = new \RuntimeException('first soft');
        $exc2     = new \LogicException('second soft');

        $instance->report($exc1);
        $instance->report($exc2);

        $soft = $instance->getSoftExceptions();
        $this->assertCount(2, $soft);
        $this->assertSame($exc1, $soft[0]);
        $this->assertSame($exc2, $soft[1]);

        ini_set('error_log', $orig);
        @unlink($logFile);
    }

    public function testRenderSoftExceptions_emptyWhenNoneReported()
    {
        $instance = $this->_freshErrorInstance();

        $rm = new \ReflectionMethod(Error::class, '_renderSoftExceptions');
        $rm->setAccessible(true);

        $this->assertSame('', $rm->invoke($instance),
            'no soft exceptions → empty string, no overlay clutter');
    }

    public function testRenderSoftExceptions_emitsReportedHeaderAndFrames()
    {
        $instance = $this->_freshErrorInstance();

        $rp = new \ReflectionProperty(Error::class, '_softExceptions');
        $rp->setAccessible(true);
        $rp->setValue($instance, [
            new \RuntimeException('counter filter blew up'),
            new \LogicException('datum filter blew up'),
        ]);

        $rm = new \ReflectionMethod(Error::class, '_renderSoftExceptions');
        $rm->setAccessible(true);
        $output = $rm->invoke($instance);

        $this->assertContains('class="dbgHeader reported"', $output,
            'soft block uses .reported CSS modifier');
        $this->assertContains('Reported (non-fatal) [2]', $output,
            'header reports the count');
        $this->assertContains('counter filter blew up', $output);
        $this->assertContains('datum filter blew up', $output);
        $this->assertContains('Reported:', $output,
            'each frame is labelled "Reported:"');
    }

    /**
     * Bypass the Error singleton's lifecycle (which depends on Router /
     * Programm state) and call renderError directly via Reflection.
     *
     * Sets `_blankScreenProtection = false` on the bare instance so its
     * destructor — which would otherwise try to ob_end_clean a buffer that
     * was never opened (because we skipped __construct) — stays inert.
     */
    private function _invokeRenderError(\Exception $exc, $hash)
    {
        $reflectionClass = new \ReflectionClass(Error::class);
        $instance        = $reflectionClass->newInstanceWithoutConstructor();

        $bsp = $reflectionClass->getProperty('_blankScreenProtection');
        $bsp->setAccessible(true);
        $bsp->setValue($instance, false);

        $method = $reflectionClass->getMethod('renderError');
        $method->setAccessible(true);
        return $method->invoke($instance, $exc, $hash);
    }

    /**
     * @return Error
     */
    private function _freshErrorInstance()
    {
        $rc       = new \ReflectionClass(Error::class);
        $instance = $rc->newInstanceWithoutConstructor();
        $bsp      = $rc->getProperty('_blankScreenProtection');
        $bsp->setAccessible(true);
        $bsp->setValue($instance, false);
        return $instance;
    }
}
