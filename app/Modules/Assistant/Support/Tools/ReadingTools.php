<?php

namespace App\Modules\Assistant\Support\Tools;

use Illuminate\Support\Facades\Log;

/**
 * Tools for analyzing and reading documents, data, and content
 */
class ReadingTools
{
    /**
     * Summarize a long text document
     */
    public static function summarizeDocument(string $content, int $maxLength = 500): string
    {
        if (empty($content)) {
            return '';
        }

        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        // If already short, return as-is
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        // Smart truncation at sentence boundary
        $truncated = substr($content, 0, $maxLength);
        $lastPeriod = strrpos($truncated, '.');
        $lastComma = strrpos($truncated, ',');
        $lastSpace = strrpos($truncated, ' ');

        $cutPoint = max($lastPeriod, $lastComma, $lastSpace);
        if ($cutPoint > $maxLength * 0.8) {
            return substr($content, 0, $cutPoint + 1) . '...';
        }

        return $truncated . '...';
    }

    /**
     * Extract key information from text
     */
    public static function extractKeyInfo(string $content): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        $keyLines = [];

        // Extract first few substantial lines
        foreach ($lines as $line) {
            if (strlen($line) > 10 && count($keyLines) < 3) {
                $keyLines[] = $line;
            }
        }

        // Extract numbers and metrics
        $numbers = [];
        preg_match_all('/\d+(?:\.\d+)?/', $content, $matches);
        if (!empty($matches[0])) {
            $numbers = array_slice(array_unique($matches[0]), 0, 5);
        }

        return [
            'key_lines' => $keyLines,
            'numbers' => $numbers,
            'line_count' => count($lines),
            'word_count' => str_word_count($content),
        ];
    }

    /**
     * Parse structured data from text
     */
    public static function parseStructuredData(string $content): array
    {
        $data = [];
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        foreach ($lines as $line) {
            // Match "key: value" pattern
            if (preg_match('/^([^:]+):\s*(.+)$/', $line, $matches)) {
                $key = strtolower(str_replace([' ', '-'], '_', trim($matches[1])));
                $data[$key] = trim($matches[2]);
            }
            // Match "• item" or "- item" pattern
            elseif (preg_match('/^[•\-\*]\s*(.+)$/', $line, $matches)) {
                $data['items'][] = trim($matches[1]);
            }
        }

        return $data;
    }

    /**
     * Analyze sentiment/tone of text
     */
    public static function analyzeTone(string $content): string
    {
        $positive = ['good', 'great', 'excellent', 'perfect', 'success', 'happy', 'amazing'];
        $negative = ['bad', 'poor', 'terrible', 'failed', 'sad', 'angry', 'disappointing'];
        $urgent = ['urgent', 'critical', 'emergency', 'immediate', 'asap', 'priority'];

        $lower = strtolower($content);

        $positiveCount = count(array_filter($positive, fn($w) => str_contains($lower, $w)));
        $negativeCount = count(array_filter($negative, fn($w) => str_contains($lower, $w)));
        $urgentCount = count(array_filter($urgent, fn($w) => str_contains($lower, $w)));

        if ($urgentCount > 0) {
            return 'urgent';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        } elseif ($positiveCount > 0) {
            return 'positive';
        }

        return 'neutral';
    }

    /**
     * Generate reading difficulty level
     */
    public static function getReadingLevel(string $content): string
    {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = str_word_count($content);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0 || $wordCount === 0) {
            return 'simple';
        }

        $avgWordLength = array_reduce(
            preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY),
            fn($carry, $word) => $carry + strlen($word),
            0
        ) / $wordCount;

        $avgSentenceLength = $wordCount / $sentenceCount;
        $flesch = 206.835 - (1.015 * $avgSentenceLength) - (84.6 * ($avgWordLength / 5));

        if ($flesch > 90) return 'very simple';
        if ($flesch > 80) return 'simple';
        if ($flesch > 70) return 'intermediate';
        if ($flesch > 60) return 'moderate';
        if ($flesch > 50) return 'complex';
        if ($flesch > 30) return 'very complex';

        return 'expert';
    }

    /**
     * Compare two pieces of text for similarity
     */
    public static function calculateSimilarity(string $text1, string $text2): float
    {
        $text1 = strtolower($text1);
        $text2 = strtolower($text2);

        // Simple Levenshtein-based similarity
        $len1 = strlen($text1);
        $len2 = strlen($text2);

        if ($len1 === 0 && $len2 === 0) {
            return 1.0;
        }

        $distance = levenshtein($text1, $text2);
        $maxLen = max($len1, $len2);

        if ($maxLen === 0) {
            return 1.0;
        }

        return (1 - ($distance / $maxLen));
    }

    /**
     * Extract time/dates from text
     */
    public static function extractTimeReferences(string $content): array
    {
        $timeRefs = [];

        // Look for date patterns
        if (preg_match_all('/\d{1,2}\/\d{1,2}\/\d{2,4}/', $content, $matches)) {
            $timeRefs['dates'] = $matches[0];
        }

        // Look for time patterns
        if (preg_match_all('/\d{1,2}:\d{2}\s?(am|pm)?/i', $content, $matches)) {
            $timeRefs['times'] = $matches[0];
        }

        // Look for day names
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $foundDays = array_filter($days, fn($day) => stripos($content, $day) !== false);
        if ($foundDays) {
            $timeRefs['days'] = array_values($foundDays);
        }

        return $timeRefs;
    }

    /**
     * Identify action items or tasks from text
     */
    public static function extractActionItems(string $content): array
    {
        $actions = [];
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        $actionKeywords = ['need to', 'must', 'should', 'todo', 'task', 'action', 'do', 'complete', 'finish'];

        foreach ($lines as $line) {
            $lower = strtolower($line);
            foreach ($actionKeywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $actions[] = $line;
                    break;
                }
            }
        }

        return $actions;
    }
}
