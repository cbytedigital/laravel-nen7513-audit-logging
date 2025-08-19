<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests\Unit;

use CbyteDigital\Nen7513AuditLogging\Enums\AuditOutcome;
use CbyteDigital\Nen7513AuditLogging\ValueObjects\AuditContext;
use CbyteDigital\Nen7513AuditLogging\Tests\TestCase;

class AuditContextTest extends TestCase
{
    /** @test */
    public function it_creates_with_defaults()
    {
        $context = new AuditContext();

        $this->assertEquals(AuditOutcome::SUCCESS, $context->outcome);
        $this->assertEquals('default', $context->authProtocol);
        $this->assertTrue($context->authorizationCheck);
        $this->assertFalse($context->treatmentRelationCheck);
        $this->assertFalse($context->consentCheck);
        $this->assertNull($context->detail);
    }

    /** @test */
    public function it_creates_with_custom_values()
    {
        $context = new AuditContext(
            detail: ['test' => 'value'],
            outcome: AuditOutcome::FAILURE,
            authProtocol: 'oauth2',
            treatmentRelationCheck: true
        );

        $this->assertEquals(['test' => 'value'], $context->detail);
        $this->assertEquals(AuditOutcome::FAILURE, $context->outcome);
        $this->assertEquals('oauth2', $context->authProtocol);
        $this->assertTrue($context->treatmentRelationCheck);
    }

    /** @test */
    public function it_creates_failed_context()
    {
        $context = AuditContext::failed('Test reason');

        $this->assertEquals(AuditOutcome::FAILURE, $context->outcome);
        $this->assertEquals(['reason' => 'Test reason'], $context->detail);
    }

    /** @test */
    public function it_creates_successful_context()
    {
        $context = AuditContext::successful(['operation' => 'test']);

        $this->assertEquals(AuditOutcome::SUCCESS, $context->outcome);
        $this->assertEquals(['operation' => 'test'], $context->detail);
    }

    /** @test */
    public function it_creates_update_context()
    {
        $context = AuditContext::forUpdate(
            ['name', 'email'],
            ['name' => 'old'],
            ['name' => 'new', 'email' => 'test@test.com']
        );

        $this->assertEquals([
            'changed_fields' => ['name', 'email'],
            'old_values' => ['name' => 'old'],
            'new_values' => ['name' => 'new', 'email' => 'test@test.com']
        ], $context->detail);
    }

    /** @test */
    public function it_creates_emergency_context()
    {
        $context = AuditContext::emergency('cardiac_arrest', 'dr_smith');

        $this->assertEquals([
            'reason' => 'cardiac_arrest',
            'authorized_by' => 'dr_smith',
            'emergency_access' => true
        ], $context->detail);
        $this->assertEquals('emergency_override', $context->authProtocol);
        $this->assertFalse($context->treatmentRelationCheck);
    }

    /** @test */
    public function it_can_add_detail()
    {
        $context = new AuditContext();
        $newContext = $context->addDetail('test', 'value');

        $this->assertNull($context->detail); // Original unchanged
        $this->assertEquals(['test' => 'value'], $newContext->detail);
    }

    /** @test */
    public function it_can_merge_detail()
    {
        $context = new AuditContext(detail: ['existing' => 'value']);
        $newContext = $context->mergeDetail(['new' => 'data']);

        $this->assertEquals(['existing' => 'value', 'new' => 'data'], $newContext->detail);
    }

    /** @test */
    public function it_converts_to_array()
    {
        $context = new AuditContext(
            detail: ['test' => 'value'],
            outcome: AuditOutcome::SUCCESS,
            authProtocol: 'oauth2'
        );

        $array = $context->toArray();

        $this->assertEquals([
            'detail' => ['test' => 'value'],
            'outcome' => 'success',
            'authorization_protocol' => 'oauth2',
            'authorization_check' => true,
            'treatment_relation_check' => false,
            'consent_check' => false,
            'source_id' => null,
            'source_type' => null,
            'ip_address' => null,
            'user_agent' => null,
        ], $array);
    }
}