# AI Assistant - Quick Reference

## Models Used in RestoCafe

### Available Models

| Model | Type | Use Case | Memory | Speed |
|-------|------|----------|--------|-------|
| **mistral** | Fast LLM | Real-time chat, orders | 4GB | ⚡⚡⚡ |
| **neural-chat** | Chat LLM | Customer service | 4GB | ⚡⚡⚡ |
| **codellama** | Code LLM | Technical queries, SQL | 4-13GB | ⚡⚡ |
| **llama2** | General LLM | Analysis, reports | 7-13GB | ⚡ |

**Currently Configured:** `mistral` (can be changed in `.env`)

---

## Tools for Reading & Analysis

All in **`ReadingTools`** class:

### 📄 Document Processing
```php
ReadingTools::summarizeDocument($text, 500)  // Smart truncation
ReadingTools::extractKeyInfo($text)          // Find key lines, numbers
ReadingTools::parseStructuredData($text)     // Parse key:value pairs
```

### 📊 Analysis
```php
ReadingTools::analyzeTone($text)             // positive/negative/urgent
ReadingTools::getReadingLevel($text)         // simple to expert
ReadingTools::extractActionItems($text)      // Find tasks to do
ReadingTools::extractTimeReferences($text)   // Find dates, times
```

### 🔍 Comparison
```php
ReadingTools::calculateSimilarity($t1, $t2)  // Compare two texts (0-1)
```

---

## Enable Ollama

### Step 1: Install Ollama
- Download from https://ollama.ai
- Run installer

### Step 2: Pull Model
```bash
ollama pull mistral
```

### Step 3: Start Server
```bash
ollama serve
```

### Step 4: Enable in App
Add to `.env`:
```
OLLAMA_ENABLED=true
```

---

## Test It Works

### Check Connection
```bash
curl http://localhost:11434/api/tags
```

### In Laravel Tinker
```php
$client = app(\App\Modules\Assistant\Support\OllamaClient::class);
$client->isAvailable() ? 'Working!' : 'Not connected';
```

---

## Architecture

```
User sends message
    ↓
AssistantManager.provider()
    ↓
Is Ollama enabled & available?
    ├─ YES → OllamaAssistantProvider
    │         ├─ Get user role
    │         ├─ Build role-aware system prompt
    │         ├─ Add conversation history
    │         ├─ Select best model
    │         └─ Call Ollama → Get response
    │
    └─ NO → DeterministicAssistantProvider
             ├─ Get user role
             └─ Return rule-based response
```

---

## Configuration Files

**`config/ollama.php`**
- `enabled` - Turn Ollama on/off
- `host` - Ollama server address
- `model` - Default model
- `models.fast/detailed/code/long` - Task-specific models
- `parameters` - Temperature, context size, etc.

**`.env`**
```
OLLAMA_ENABLED=true|false
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=mistral
OLLAMA_TEMPERATURE=0.7
OLLAMA_TIMEOUT=60
```

---

## Role-Based Responses

Each role gets tailored responses:

- **Admin** → Strategic insights, metrics, system health
- **Manager** → Operations, efficiency, revenue
- **Waiter** → Customer service, orders, tables
- **Cashier** → Payments, invoices, transactions
- **Kitchen** → Recipes, ingredients, orders

---

## Fallback Mechanism

If Ollama fails or is disabled:
✅ Automatically uses rule-based provider
✅ No errors, service continues
⚠️ Check logs: `storage/logs/laravel.log`

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Connection refused" | Make sure `ollama serve` is running |
| Slow responses | Use `mistral` model instead of `llama2` |
| Model not found | Run `ollama pull mistral` |
| Out of memory | Use smaller model or reduce context size |
| No responses | Check `OLLAMA_ENABLED=true` in `.env` |

---

## Files Added

✅ `config/ollama.php` - Configuration
✅ `app/Modules/Assistant/Support/OllamaClient.php` - HTTP client
✅ `app/Modules/Assistant/Support/OllamaAssistantProvider.php` - LLM provider  
✅ `app/Modules/Assistant/Support/Tools/ReadingTools.php` - Analysis tools
✅ `app/Modules/Assistant/Support/AssistantServiceProvider.php` - DI registration
✅ `app/Modules/Assistant/Support/OllamaCommandHelper.php` - Helper guides
✅ `docs/ai-assistant-ollama.md` - Full documentation

---

## Next Steps

1. Install Ollama from ollama.ai
2. Run: `ollama pull mistral`
3. Run: `ollama serve`
4. Set `OLLAMA_ENABLED=true` in `.env`
5. Test with a message in the assistant
