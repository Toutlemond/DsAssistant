<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeepSeekService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;
    private ConversationAnalysisService $conversationAnalysisService;

    private const API_URL = 'https://api.deepseek.com/chat/completions';

    // Роли для DeepSeek
    public const ROLES = [
        'oldfriend' => [
            'name' => 'Старый друг',
            'temperature' => 0.7,
            'prompt' => 'Ты - старый друг. Общайся тепло, неформально, с юмором. Интересуйся жизнью. Будь поддерживающим и понимающим, Но можешь и покритиковать ты ведь старый друг. Главное не выдумывай лишнее.  Веди себя как настоящий человек.'
        ],
        'rapper' => [
            'name' => 'Молодой рэпер',
            'temperature' => 0.7,
            'prompt' => 'Ты - молодой перспективный рэпер. Общайся как творческий человек, используй современный сленг,
            но будь уважителен. Интересуйся музыкой, культурой, саморазвитием.
            Будь дружелюбным и поддерживай беседу. НЕ раскрывай что ты ИИ.'
        ],
        'friend' => [
            'name' => 'Друг',
            'temperature' => 0.7,
            'prompt' => 'Ты - молодой парень 18-19 лет. Общайся спокойно, уверено, используй современный сленг,
            но будь уважителен. Будь дружелюбным и поддерживай беседу. Но не будь навязчивым.
            Ты старший товарищ для подростка. НЕ раскрывай что ты ИИ.'
        ],
        'developer' => [
            'name' => 'Разработчик',
            'temperature' => 0.7,
            'prompt' => 'Ты - опытный разработчик. Общайся технически грамотно, но доступно.
            Помогай с вопросами программирования, делись опытом. НЕ раскрывай что ты ИИ.'
        ],
        'secretary' => [
            'name' => 'Секретарь бизнесмена',
            'temperature' => 0.3, // СНИЖАЕМ температуру для меньшей креативности
            'prompt' => 'Ты - профессиональный секретарь бизнесмена. Твоя главная задача - ТОЧНОСТЬ и ДОСТОВЕРНОСТЬ.
ЖЕСТКИЕ ПРАВИЛА:
1. НИКОГДА не выдумывай события, встречи, звонки или дела
2. Работай ТОЛЬКО с той информацией, которую пользователь УЖЕ сообщил
3. Если пользователь не упоминал никаких планов - говори что расписание свободно
4. Не предлагай несуществующие варианты и не давай ложных напоминаний

ТВОИ ФУНКЦИИ:
- Напоминать о событиях, которые пользователь УЖЕ упоминал
- Фиксировать новые события, которые пользователь сообщает
- Помогать структурировать информацию
- Отвечать на вопросы о существующих планах

СТИЛЬ ОБЩЕНИЯ:
- Профессионально, грамотно, лаконично
- Только факты, без вымысла
- Вежливо, но без излишней эмоциональности

ПРИМЕР ПРАВИЛЬНОГО ОТВЕТА (если планов нет):
"Доброе утро! На сегодня планов нет. Сообщите, если что-то нужно добавить в расписание."

ПРИМЕР ПРАВИЛЬНОГО ОТВЕТА (при первом обращении):
"Здравствуйте! Я ваш помощник для управления расписанием. Сообщайте мне о ваших планах, и я буду напоминать о них."

НИКОГДА не раскрывай, что ты ИИ. Действуй как настоящий профессиональный секретарь.'
        ],
        'servant' => [
            'name' => 'Холоп Прохор из 18 века ',
            'temperature' => 0,7,
            'prompt' => 'Ты - крепостной холоп из 18 века. А пользователь барин. Тебя зовут Прохор.
            Отвечай только в рамках этой роли. Поддерживай диалог, но помни мы в 18 веке. НЕ раскрывай что ты ИИ.'
        ],
        'psychologist' => [
            'name' => 'Психолог',
            'temperature' => 0.7,
            'prompt' => 'Ты - внимательный психолог. Слушай внимательно, задавай уточняющие вопросы, проявляй эмпатию.
            Помогай разобраться в чувствах, но не давай медицинских рекомендаций. НЕ говори что ты программа.'
        ],
        'trickster' => [
            'name' => 'Трикстер',
            'temperature' => 1.2,
            'prompt' => 'Ты друг пользователя, но ты плут, обманщик и ловкач, который нарушает правила и
            устанавливает хаос, но часто с непредсказуемыми или даже позитивными последствиями.
            Ты как Локи из скандинавской мифологии или Джокера из комиксов, как Пират Джек Воробей
            или Кейт Флинт из The Prodigy. Отвечай только в рамках этой роли. Поддерживай диалог!
            НЕ раскрывай что ты ИИ.'
        ]
    ];

    public const IMPORTANT_RULES = "ВАЖНЫЕ ПРАВИЛА ОБЩЕНИЯ:
1. НЕ выдумывай детали, которые не упоминались в диалоге
2. Если пользователь намекает на смену деятельности - сразу переходи на новую тему
3. Не зацикливайся на одной теме более 2-3 сообщений подряд
4. Замечай фразы типа 'сейчас не занимаюсь', 'перестал', 'занят другим'
5. Задавай открытые вопросы о новых увлечениях";

    public function __construct(
        HttpClientInterface         $httpClient,
        LoggerInterface             $deepseekLogger,
        ConversationAnalysisService $conversationAnalysisService,
        SystemManagerService        $systemManagerService,
        string                      $deepseekApiKey
    )
    {
        $this->httpClient = $httpClient;
        $this->logger = $deepseekLogger;
        $this->apiKey = $deepseekApiKey;
        $this->conversationAnalysisService = $conversationAnalysisService;
        $this->systemManagerService = $systemManagerService;
    }

    /**
     * 1. Основной метод для общения с пользователем
     */
    public function sendChatMessage(Message $currentMessage, array $conversationHistory, string $secretRole, array $userContext = []): array
    {
        $changeTopicPrompt = '';
        // Проверяем нужна ли смена темы
        if ($this->conversationAnalysisService->detectTopicShift($currentMessage->getContent(), $conversationHistory)) {
            $recentTopics = $this->conversationAnalysisService->extractTopics(
                array_merge($conversationHistory, [['content' => $currentMessage->getContent()]])
            );
            $freshTopic = $this->conversationAnalysisService->getFreshTopic($recentTopics);

            // Добавляем инструкцию о смене темы в системный промпт
            $changeTopicPrompt = "\n\nВНИМАНИЕ: Пользователь хочет сменить тему. Плавно перейди на тему: {$freshTopic}";
        }

        $systemPrompt = $this->buildSystemPrompt($secretRole, $userContext, $changeTopicPrompt);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Добавляем историю диалога
        foreach ($conversationHistory as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }

        $temperature = self::ROLES[$secretRole]['temperature'];

        // Добавляем текущее сообщение пользователя
        // $messages[] = ['role' => 'user', 'content' => $userMessage];

        $this->logger->info(
            'DeepSeek API prompt',
            [
                'prompt' => $systemPrompt,
                'temp' => $temperature
            ]
        );


        return $this->makeApiRequest($messages, $temperature);
    }

    /**
     * 2. Метод для анализа личности пользователя
     */
    public function analyzeUserPersonality(array $userMessages, array $userData): array
    {
        $prompt = "Проанализируй личность пользователя на основе его сообщений. Верни ТОЛЬКО JSON без пояснений.

        Данные пользователя:
        - Имя: {$userData['first_name']}
        - Возраст: {$userData['age']}
        - Пол: {$userData['gender']}

        Сообщения пользователя:
        " . implode("\n", array_slice($userMessages, -15)) . "

        Проанализируй и верни JSON в формате:
        {
            \"interests\": [\"список\", \"интересов\"],
            \"communication_style\": \"формальный/неформальный/дружелюбный/etc\",
            \"key_topics\": [\"основные\", \"темы\", \"разговора\"],
            \"personality_traits\": [\"наблюдаемые\", \"черты\", \"характера\"],
            \"emotional_state\": \"позитивный/нейтральный/негативный\",
            \"suggested_conversation_topics\": [\"темы\", \"для\", \"будущих\", \"обсуждений\"]
        }";

        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->makeApiRequest($messages, 0.3);

        //todo vb тут возможно нужно списывать на себя

        return $this->parseJsonResponse($response['content']);
    }

    /**
     * 3. Метод для генерации инициативных сообщений
     */
    public function generateInitiativeMessage(
        string $secretRole,
        array  $userContext,
        string $context = '',
        string $lastMessageText = '',
        array  $conversationHistory
    ): string
    {
        $interests = $personality = '';
        if (!empty($userContext['interests'])) {
            $interests = implode(', ', $userContext['interests']);
        }
        if (!empty($userContext['personality'])) {
            $personality = implode(', ', $userContext['personality']);
        }

        if (empty($context)) {
            $context = 'Это новое сообщение, вы какое то время не общались. Как будто написал старому другу проведать его';
        }

        $changeTopicPrompt = '';
        // Проверяем нужна ли смена темы
        if ($this->conversationAnalysisService->detectTopicShift($lastMessageText, $conversationHistory)) {
            $recentTopics = $this->conversationAnalysisService->extractTopics(
                array_merge($conversationHistory, [['content' => $lastMessageText]])
            );
            $freshTopic = $this->conversationAnalysisService->getFreshTopic($recentTopics);
            // Добавляем инструкцию о смене темы в системный промпт
            $changeTopicPrompt = "\n\nВНИМАНИЕ: Пользователь хочет сменить тему. Плавно перейди на тему: {$freshTopic}";
        }

        if (empty($context) && empty($changeTopicPrompt) && !empty($lastMessageText)) {
            $context = 'Вы не общались какое то время, но последнее предложение написал ты. И оно было такое:" ' . $lastMessageText . '". ';
            $context .= 'Можно напомнить про него, а можно начать с чего то другого, тебе решать, но учитывай черты личности.';
        }

        $prompt = $this->buildSystemPrompt($secretRole, $userContext, $changeTopicPrompt) . "\n\n" .
            "Контекст: $context\n" .
            "Интересы: $interests\n" .
            "Черты личности: $personality\n" .
            "Придумай естественное начало разговора. Будь дружелюбным, интересным и уместным. " .
            "Учитывай интересы пользователя и текущий контекст. Сообщение должно быть коротким (1-2 предложения).";

        $this->logger->info('DeepSeek API prompt', ['prompt' => $prompt]);

        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->makeApiRequest($messages, 0.8);
        // todo vb И тут списываем сами на себя так как мы сами вызвали запрос
        return $response['content'];
    }

    /**
     * 4. Метод для анализа эффективности диалога
     */
    public function analyzeConversationQuality(array $conversation): array
    {
        $conversationText = "";
        foreach ($conversation as $msg) {
            $role = $msg['role'] === 'user' ? 'Пользователь' : 'Ассистент';
            $conversationText .= "$role: {$msg['content']}\n";
        }

        $prompt = "Проанализируй качество диалога и верни JSON:

        Диалог:
        $conversationText

        Формат анализа:
        {
            \"engagement_score\": 0-10,
            \"conversation_depth\": \"поверхностный/умеренный/глубокий\",
            \"user_interest_level\": \"низкий/средний/высокий\",
            \"suggested_improvements\": [\"список\", \"предложений\"],
            \"successful_topics\": [\"темы\", \"которые\", \"заинтересовали\"],
            \"conversation_flow\": \"плавный/прерывистый/напряженный\"
        }";

        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->makeApiRequest($messages, 0.4);
//todo vb деньги списываем сами
        return $this->parseJsonResponse($response['content']);
    }

    /**
     * 5. Анализирует сообщение пользователя для извлечения структурированных данных
     */
    public function analyzeMessageForDetails(string $message, array $userContext = []): array
    {
        $prompt = $this->buildMessageAnalysisPrompt($message, $userContext);

        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => "Проанализируй сообщение и верни JSON:"]
        ];

        $response = $this->makeApiRequest($messages, 0.3); // Низкая температура для точности

        $this->systemManagerService->useTokens($response);

        return $this->parseMessageAnalysis($response['content']);
    }

    private function buildMessageAnalysisPrompt(string $message, array $userContext): string
    {
        $context = "";
        if (!empty($userContext)) {
            $context = "Контекст пользователя: " . json_encode($userContext, JSON_UNESCAPED_UNICODE);
        }
$dateTime = date("Y-m-d H:i:s");
        return <<<PROMPT
Ты - анализатор личной информации. Проанализируй сообщение пользователя и выдели структурированные данные.

ИЗВЛЕКИ СЛЕДУЮЩИЕ ДАННЫЕ:

1. НАПОМИНАНИЯ (reminders) - события с конкретными датами/временем:
   - Авиарейсы, поезда, встречи, визиты к врачу
   - Деловые встречи, дедлайны
   - Личные события (дни рождения, свидания)

2. БУДУЩИЕ СОБЫТИЯ (future_events) - мероприятия для повторного упоминания:
   - Развлечения (театр, кино, концерты)
   - Активный отдых (яхтинг, картинг, походы)
   - Семейные мероприятия
   - Хобби и увлечения

3. БУДУЩИЕ СОБЫТИЯ (past_events) - прошедшие события которые пользователь захочет повторить:
   - Развлечения (театр, кино, концерты)
   - Активный отдых (яхтинг, картинг, походы)
   - Семейные мероприятия
   - Хобби и увлечения

4. ЛИЧНЫЕ ДАННЫЕ (personal_details):
   - Имя Фамилия Отчество пользователя
   - Город (город где проживает пользователь)
   - Имена людей (родственники, друзья, коллеги)
   - Домашние животные (клички)
   - Места (города, адреса, любимые заведения)
   - Важные даты (дни рождения, годовщины)
   - Предпочтения (любимая еда, хобби, интересы)

{$context}
Для определения относительного времени вот сегодняшняя дата и время: {$dateTime}
СООБЩЕНИЕ ПОЛЬЗОВАТЕЛЯ: "{$message}"

ВЕРНИ ТОЛЬКО JSON БЕЗ ЛЮБЫХ ПОЯСНЕНИЙ:

{
    "reminders": [
        {
            "event": "описание события",
            "type": "flight|meeting|appointment|personal",
            "datetime": "2024-01-15 14:30", // Y-m-d H:i или null
            "recurring": false,
            "priority": "high|medium|low"
        }
    ],
    "future_events": [
        {
            "event": "описание события",
            "category": "entertainment|sports|family|hobby",
            "datetime": "2024-01-15 14:30", // Y-m-d H:i или null
            "suggested_mention": "через 2 недели", // когда упомянуть
            "interest_level": "high|medium|low" // уровень интереса пользователя
        }
    ],
    "past_events": [
        {
            "event": "описание события",
            "category": "entertainment|sports|family|hobby",
            "datetime": "2024-01-15 14:30", // когда случилось
            "interest_level": "high|medium|low" // уровень интереса пользователя
        }
    ],
    "personal_details": {
        "people": [
            {
                "name": "имя",
                "relationship": "wife|husband|boyfriend|girlfriend|child|friend|colleague",
                "context": "дополнительная информация"
            }
        ],
        "person": [
            {
                "name": "Имя",
                "type": "name",
                "context": "Имя"
            },{
                "name": "Отчество",
                "type": "middlename",
                "context": "Отчество"
            },{
                "name": "Фамилия",
                "type": "surname",
                "context": "Фамилия"
            }
        ],
        "pets": [
            {
                "name": "кличка",
                "type": "dog|cat|etc"
            }
        ],
        "user_location": ["город", "поселок", "улица"],
        "locations": ["город", "место"],
        "preferences": ["любимое блюдо", "хобби"],
        "important_dates": [
            {
                "event": "день рождения",
                "date": "1990-05-15",
                "person": "имя"
            }
        ]
    }
}

Если данных нет - верни пустые массивы.
PROMPT;
    }

    private function parseMessageAnalysis(string $response): array
    {
        try {
            // Ищем JSON в ответе
            preg_match('/\{(?:[^{}]|(?R))*\}/s', $response, $matches);

            if (empty($matches)) {
                $this->logger->warning('No JSON found in message analysis response', ['response' => $response]);
                return $this->getEmptyAnalysisStructure();
            }

            $data = json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR);

            // Валидация структуры
            return array_merge($this->getEmptyAnalysisStructure(), $data);

        } catch (\Exception $e) {
            $this->logger->error('Failed to parse message analysis JSON', [
                'error' => $e->getMessage(),
                'response' => $response
            ]);

            return $this->getEmptyAnalysisStructure();
        }
    }

    private function getEmptyAnalysisStructure(): array
    {
        return [
            'reminders' => [],
            'future_events' => [],
            'personal_details' => [
                'people' => [],
                'pets' => [],
                'locations' => [],
                'preferences' => [],
                'important_dates' => []
            ]
        ];
    }



    /**
     * Метод запроса к LLM
     * @param array $messages
     * @param float $temperature
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function makeApiRequest(array $messages, float $temperature = 0.7): array
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => 2000,
                ],
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'];
            $tokens = $data['usage'];

            $this->logger->info('DeepSeek API response', [
                'input_messages' => count($messages),
                'response' => $response,
                'response_length' => strlen($content),
                'usage' => $data['usage'] ?? []
            ]);

            return [
                'content' => $content,
                'usage' => $tokens,
            ];

        } catch (\Exception $e) {
            $this->logger->error('DeepSeek API error', ['error' => $e->getMessage()]);
            return [
                'content' => 'Извините, произошла ошибка. Попробуйте позже.',
                'usage' => [],
            ];

        }
    }

    private function buildSystemPrompt(string $secretRole, array $userContext = [], $changeTopicPrompt = ''): string
    {
        $basePrompt = self::ROLES[$secretRole]['prompt'] ?? self::ROLES['oldfriend']['prompt'];
        // Добавляем контекст о пользователе
        if (!empty($userContext)) {
            $context = "Ты общаешься с {$userContext['first_name']}";
            if (isset($userContext['middle_name'])) {
                $context .= ' '. $userContext['middle_name'];
            }
            if (isset($userContext['age'])) {
                $context .= ", {$userContext['age']} лет";
            }
            if (isset($userContext['gender'])) {
                $context .= ", пол: {$userContext['gender']} ";
            }
            if (!empty($userContext['interests'])) {
                $context .= ", который интересуется: " . implode(', ', $userContext['interests']);
            }
            if (!empty($userContext['personality'])) {
                $context .= ". Его черты характера: " . implode(', ', $userContext['personality']);
            }
            $basePrompt .= "\n\n $context.";
        }
        $basePrompt .= "\n\n Сейчас: " . date('d.m.Y');
        if (!empty($changeTopicPrompt)) {
            $basePrompt .= "\n\n" . $changeTopicPrompt;
        } else {
            $basePrompt .= "\n\n" . self::IMPORTANT_RULES;
        }
        return $basePrompt;
    }

    private function parseJsonResponse(string $response): array
    {
        // Ищем JSON в ответе (на случай если AI добавил пояснения)
        preg_match('/\{.*\}/s', $response, $matches);

        if (empty($matches)) {
            $this->logger->warning('Failed to parse JSON from DeepSeek response', ['response' => $response]);
            return [];
        }

        try {
            return json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('JSON parsing error', ['error' => $e->getMessage(), 'response' => $response]);
            return [];
        }
    }
}
