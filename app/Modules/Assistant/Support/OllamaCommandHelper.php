<?php

namespace App\Modules\Assistant\Support;

class OllamaCommandHelper
{
    /**
     * Install Ollama instructions
     */
    public static function getInstallationGuide(): string
    {
        return <<<'GUIDE'
╔════════════════════════════════════════════════════════════════╗
║              OLLAMA INSTALLATION & SETUP GUIDE                 ║
╚════════════════════════════════════════════════════════════════╝

1. INSTALL OLLAMA
   Windows: Download from https://ollama.ai
   macOS: brew install ollama
   Linux: curl https://ollama.ai/install.sh | sh

2. START OLLAMA SERVER
   ollama serve

3. PULL MODELS (in another terminal)
   # Fast responses (recommended for real-time)
   ollama pull mistral

   # Detailed analysis and reasoning
   ollama pull neural-chat

   # Code understanding
   ollama pull codellama

   # Long document processing
   ollama pull llama2

4. VERIFY INSTALLATION
   ollama list

5. ENABLE IN APPLICATION
   Set OLLAMA_ENABLED=true in .env
   Restart your application

6. AVAILABLE MODELS
   mistral          - Fast, 7B params, great for chat
   neural-chat      - 7B, optimized for conversation
   codellama        - Code understanding and generation
   llama2           - 7B/13B, general purpose
   dolphin-mixtral  - 7B, instruction following

7. SYSTEM REQUIREMENTS
   Minimum: 4GB RAM, 4GB disk space
   Recommended: 8GB+ RAM for faster inference

8. PERFORMANCE TIPS
   - Use smaller models (mistral, neural-chat) for real-time
   - Use larger models (llama2) for detailed analysis
   - Increase num_thread in config for more CPU usage
   - GPU support available in Ollama (NVIDIA, AMD)

GUIDE;
    }

    /**
     * Get troubleshooting guide
     */
    public static function getTroubleshootingGuide(): string
    {
        return <<<'GUIDE'
╔════════════════════════════════════════════════════════════════╗
║           OLLAMA TROUBLESHOOTING GUIDE                          ║
╚════════════════════════════════════════════════════════════════╝

ISSUE: "Connection refused" error
SOLUTION:
  1. Ensure Ollama is running: ollama serve
  2. Check OLLAMA_HOST in .env (default: http://localhost:11434)
  3. Try: curl http://localhost:11434/api/tags

ISSUE: Model not responding
SOLUTION:
  1. Check model is installed: ollama list
  2. Pull model if missing: ollama pull mistral
  3. Monitor system resources (RAM, CPU)
  4. Check logs: tail logs/assistant.log

ISSUE: Slow responses
SOLUTION:
  1. Use faster model: mistral instead of llama2
  2. Reduce num_predict in config (shorter responses)
  3. Increase OLLAMA_NUM_THREAD for more CPU usage
  4. Use GPU if available: export CUDA_VISIBLE_DEVICES=0

ISSUE: Model running out of memory
SOLUTION:
  1. Use smaller model (mistral = 4GB, llama2-13b = 13GB)
  2. Reduce context_size in config
  3. Add GPU support for larger models
  4. Monitor: ollama status

ISSUE: Responses are generic/poor quality
SOLUTION:
  1. Check system prompt in OllamaAssistantProvider
  2. Adjust temperature (0.3 = consistent, 0.9 = creative)
  3. Try different model for the task
  4. Review role-based prompts

GUIDE;
    }

    /**
     * Get model recommendations
     */
    public static function getModelRecommendations(): array
    {
        return [
            'mistral' => [
                'size' => '4GB',
                'speed' => 'Very Fast',
                'quality' => 'Good',
                'best_for' => 'Real-time chat, order taking',
                'pros' => ['Fast responses', 'Low memory', 'Good instruction following'],
                'cons' => ['Less detailed than larger models'],
            ],
            'neural-chat' => [
                'size' => '4GB',
                'speed' => 'Very Fast',
                'quality' => 'Good',
                'best_for' => 'Conversational AI, customer service',
                'pros' => ['Optimized for chat', 'Natural responses', 'Fast'],
                'cons' => ['Less suitable for code'],
            ],
            'codellama' => [
                'size' => '4GB-13GB',
                'speed' => 'Fast-Moderate',
                'quality' => 'Excellent for code',
                'best_for' => 'Query help, technical questions',
                'pros' => ['Code understanding', 'SQL support', 'Technical accuracy'],
                'cons' => ['Slower than mistral'],
            ],
            'llama2' => [
                'size' => '7GB-13GB',
                'speed' => 'Moderate',
                'quality' => 'Excellent',
                'best_for' => 'Detailed analysis, reports',
                'pros' => ['High quality', 'Good reasoning', 'Widely used'],
                'cons' => ['Slower, more memory needed'],
            ],
            'dolphin-mixtral' => [
                'size' => '5GB',
                'speed' => 'Fast',
                'quality' => 'Excellent',
                'best_for' => 'All-around use',
                'pros' => ['Balance of speed and quality', 'Good instruction following'],
                'cons' => ['Slightly more memory than mistral'],
            ],
        ];
    }
}
