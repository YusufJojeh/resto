<?php

namespace Tests\Unit\Support;

use App\Modules\Assistant\Support\AssistantIntent;
use App\Modules\Assistant\Support\AssistantIntentDetector;
use Tests\TestCase;

class AssistantIntentDetectorTest extends TestCase
{
    public function test_detects_english_reports_intent(): void
    {
        $detector = new AssistantIntentDetector();

        $this->assertSame(
            AssistantIntent::REVENUE,
            $detector->detect('Show today revenue and top items'),
        );
    }

    public function test_detects_arabic_inventory_intent(): void
    {
        $detector = new AssistantIntentDetector();

        $this->assertSame(
            AssistantIntent::INVENTORY,
            $detector->detect('اعرض تنبيهات المخزون المنخفض'),
        );
    }

    public function test_unknown_prompts_fall_back_to_general_intent(): void
    {
        $detector = new AssistantIntentDetector();

        $this->assertSame(
            AssistantIntent::GENERAL_RESTAURANT_QUESTION,
            $detector->detect('Tell me something useful'),
        );
    }
}
