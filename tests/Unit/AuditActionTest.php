<?php

namespace CbyteDigital\Nen7513AuditLogging\Tests\Unit;

use CbyteDigital\Nen7513AuditLogging\Enums\AuditAction;
use CbyteDigital\Nen7513AuditLogging\Tests\TestCase;

class AuditActionTest extends TestCase
{
    /** @test */
    public function it_has_correct_enum_values()
    {
        $this->assertEquals('C', AuditAction::CREATE->value);
        $this->assertEquals('R', AuditAction::READ->value);
        $this->assertEquals('U', AuditAction::UPDATE->value);
        $this->assertEquals('D', AuditAction::DELETE->value);
        $this->assertEquals('E', AuditAction::EXECUTE->value);
    }

    /** @test */
    public function it_provides_descriptions()
    {
        $this->assertEquals('Create', AuditAction::CREATE->description());
        $this->assertEquals('Read', AuditAction::READ->description());
        $this->assertEquals('Update', AuditAction::UPDATE->description());
        $this->assertEquals('Delete', AuditAction::DELETE->description());
        $this->assertEquals('Execute', AuditAction::EXECUTE->description());
    }

    /** @test */
    public function it_identifies_data_changes()
    {
        $this->assertTrue(AuditAction::CREATE->isDataChange());
        $this->assertTrue(AuditAction::UPDATE->isDataChange());
        $this->assertTrue(AuditAction::DELETE->isDataChange());
        $this->assertFalse(AuditAction::READ->isDataChange());
        $this->assertFalse(AuditAction::EXECUTE->isDataChange());
    }

    /** @test */
    public function it_identifies_read_actions()
    {
        $this->assertTrue(AuditAction::READ->isReadAction());
        $this->assertFalse(AuditAction::CREATE->isReadAction());
        $this->assertFalse(AuditAction::UPDATE->isReadAction());
        $this->assertFalse(AuditAction::DELETE->isReadAction());
        $this->assertFalse(AuditAction::EXECUTE->isReadAction());
    }
}