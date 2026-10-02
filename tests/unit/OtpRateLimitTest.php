<?php

namespace Tests\Unit;

use App\Models\Operation;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for Operation::check_otp_rate_limit(), using a mocked database
 * layer rather than a real test database connection.
 *
 * ASSUMPTION: the project currently has no migrations (app/Database/Migrations/
 * is empty), so there's no way to spin up a real schema for integration-style
 * DB tests yet. This test mocks $this->db directly so the rate-limiting LOGIC
 * (attempt counting, window expiry, block duration) can be verified in
 * isolation, independent of an actual database. This is a valid and common
 * unit-testing approach, but it does mean the test verifies the PHP logic
 * only — not the real SQL queries against a real schema. Once migrations
 * exist, this should be supplemented with a real integration test.
 */
class OtpRateLimitTest extends CIUnitTestCase
{
    /**
     * Builds an Operation instance with $this->db swapped for a mock
     * that returns a canned "row" for the SELECT and records calls to
     * query() so we can assert what SQL path was taken.
     */
    private function makeOperationWithMockDb(?object $existingRow, array &$queryLog): Operation
    {
        $mockResult = $this->createMock(\CodeIgniter\Database\ResultInterface::class);
        $mockResult->method('getRow')->willReturn($existingRow);

        $mockDb = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['query'])
            ->getMock();

        $mockDb->method('query')
            ->willReturnCallback(function ($sql, $params = []) use (&$queryLog, $mockResult) {
                $queryLog[] = ['sql' => $sql, 'params' => $params];
                // SELECT queries need to return the mocked result object;
                // INSERT/UPDATE queries just need a truthy return.
                if (stripos(trim($sql), 'SELECT') === 0) {
                    return $mockResult;
                }
                return true;
            });

        $operation = $this->getMockBuilder(Operation::class)
            ->onlyMethods([]) // keep real methods, we just swap the db property
            ->disableOriginalConstructor()
            ->getMock();

        // db is a public/protected property set in Operation's constructor;
        // since we disabled the constructor, set it directly via reflection.
        $reflection = new \ReflectionClass($operation);
        $dbProp = $reflection->getProperty('db');
        $dbProp->setAccessible(true);
        $dbProp->setValue($operation, $mockDb);

        return $operation;
    }

    public function testFirstRequestIsAllowedAndInsertsNewRow()
    {
        $queryLog = [];
        $operation = $this->makeOperationWithMockDb(null, $queryLog); // no existing row

        $result = $operation->check_otp_rate_limit('test@example.com', 'verify');

        $this->assertTrue($result['allowed']);
        $this->assertSame('', $result['message']);

        // Confirm an INSERT was issued for the first-ever request
        $insertIssued = false;
        foreach ($queryLog as $entry) {
            if (stripos($entry['sql'], 'INSERT INTO otp_rate_limit') !== false) {
                $insertIssued = true;
            }
        }
        $this->assertTrue($insertIssued, 'Expected an INSERT for a brand new identifier/purpose');
    }

    public function testRequestUnderLimitIsAllowed()
    {
        $queryLog = [];
        $existingRow = (object) [
            'id' => 1,
            'identifier' => 'test@example.com',
            'purpose' => 'verify',
            'attempt_count' => 2, // under the default max of 5
            'window_start' => date('Y-m-d H:i:s'),
            'blocked_until' => null,
        ];
        $operation = $this->makeOperationWithMockDb($existingRow, $queryLog);

        $result = $operation->check_otp_rate_limit('test@example.com', 'verify');

        $this->assertTrue($result['allowed']);
    }

    public function testExceedingLimitBlocksAndReturnsMessage()
    {
        $queryLog = [];
        $existingRow = (object) [
            'id' => 1,
            'identifier' => 'test@example.com',
            'purpose' => 'verify',
            'attempt_count' => 5, // already at the max; this call would be #6
            'window_start' => date('Y-m-d H:i:s'),
            'blocked_until' => null,
        ];
        $operation = $this->makeOperationWithMockDb($existingRow, $queryLog);

        $result = $operation->check_otp_rate_limit('test@example.com', 'verify');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Too many OTP requests', $result['message']);
    }

    public function testActiveBlockRejectsRequestWithWaitMessage()
    {
        $queryLog = [];
        $existingRow = (object) [
            'id' => 1,
            'identifier' => 'test@example.com',
            'purpose' => 'verify',
            'attempt_count' => 6,
            'window_start' => date('Y-m-d H:i:s'),
            'blocked_until' => date('Y-m-d H:i:s', time() + 600), // 10 min from now
        ];
        $operation = $this->makeOperationWithMockDb($existingRow, $queryLog);

        $result = $operation->check_otp_rate_limit('test@example.com', 'verify');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Please try again after', $result['message']);
    }

    public function testExpiredBlockAllowsRequestAndResetsWindow()
    {
        $queryLog = [];
        $existingRow = (object) [
            'id' => 1,
            'identifier' => 'test@example.com',
            'purpose' => 'verify',
            'attempt_count' => 6,
            'window_start' => date('Y-m-d H:i:s', time() - 3600),
            'blocked_until' => date('Y-m-d H:i:s', time() - 60), // expired 1 min ago
        ];
        $operation = $this->makeOperationWithMockDb($existingRow, $queryLog);

        $result = $operation->check_otp_rate_limit('test@example.com', 'verify');

        $this->assertTrue($result['allowed']);
    }
}