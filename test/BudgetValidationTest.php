<?php

use PHPUnit\Framework\TestCase;

class BudgetValidationTest extends TestCase
{
    /**
     * Test that valid email addresses pass validation
     */
    public function testValidEmailValidation()
    {
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '$email' should be valid"
            );
        }
    }

    /**
     * Test that invalid email addresses fail validation
     */
    public function testInvalidEmailValidation()
    {
        $invalidEmails = [
            'not-an-email',
            '@example.com',
            'user@',
            'user name@example.com',
        ];
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '$email' should be invalid"
            );
        }
    }

    /**
     * Test that threshold percentages are within valid range (1-99)
     */
    public function testThresholdPercentageValidation()
    {
        // Valid thresholds
        $validThresholds = [1, 25, 50, 75, 99];
        foreach ($validThresholds as $threshold) {
            $this->assertTrue(
                is_numeric($threshold) && $threshold >= 1 && $threshold <= 99,
                "Threshold $threshold should be valid"
            );
        }
        
        // Invalid thresholds
        $invalidThresholds = [0, -1, 100, 101, 150, -50];
        foreach ($invalidThresholds as $threshold) {
            $this->assertFalse(
                is_numeric($threshold) && $threshold >= 1 && $threshold <= 99,
                "Threshold $threshold should be invalid"
            );
        }
    }

    /**
     * Test that non-numeric threshold values are rejected
     */
    public function testNonNumericThresholdValidation()
    {
        $invalidValues = ['abc', 'fifty', '50%', null];
        
        foreach ($invalidValues as $value) {
            if ($value === null) {
                $this->assertNull($value);
            } else {
                $this->assertFalse(
                    is_numeric($value),
                    "Value '$value' should not be numeric"
                );
            }
        }
    }

    /**
     * Test threshold array validation
     */
    public function testThresholdArrayValidation()
    {
        $validThresholdArrays = [
            [],
            [50],
            [25, 50, 75],
            [10, 20, 30, 40, 50, 60, 70, 80, 90],
        ];
        
        foreach ($validThresholdArrays as $thresholds) {
            $this->assertIsArray($thresholds);
            
            foreach ($thresholds as $threshold) {
                $this->assertTrue(
                    is_numeric($threshold) && $threshold >= 1 && $threshold <= 99,
                    "All thresholds in array should be valid"
                );
            }
        }
    }

    /**
     * Test that duplicate thresholds are handled (they should be allowed)
     */
    public function testDuplicateThresholds()
    {
        $thresholdsWithDuplicates = [50, 50, 75, 75];
        
        $this->assertIsArray($thresholdsWithDuplicates);
        $this->assertCount(4, $thresholdsWithDuplicates);
        
        // Remove duplicates
        $uniqueThresholds = array_unique($thresholdsWithDuplicates);
        $this->assertCount(2, $uniqueThresholds);
    }

    /**
     * Test that threshold sorting doesn't affect functionality
     */
    public function testThresholdOrdering()
    {
        $unorderedThresholds = [75, 25, 90, 50];
        $orderedThresholds = [25, 50, 75, 90];
        
        // Both should be valid
        foreach ($unorderedThresholds as $threshold) {
            $this->assertTrue($threshold >= 1 && $threshold <= 99);
        }
        
        sort($unorderedThresholds);
        $this->assertEquals($orderedThresholds, $unorderedThresholds);
    }
}
