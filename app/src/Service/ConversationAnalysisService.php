<?php

namespace App\Service;

use App\Entity\Message;

class ConversationAnalysisService
{
    private const TOPIC_CATEGORIES = [
        'hobbies' => ['музыка', 'спорт', 'книги', 'фильмы', 'путешествия', 'кулинария', 'фотография'],
        'learning' => ['курсы', 'языки', 'навыки', 'образование', 'чтение', 'саморазвитие'],
        'work' => ['проекты', 'работа', 'карьера', 'бизнес', 'стартапы'],
        'life' => ['планы', 'мечты', 'цели', 'отношения', 'друзья', 'семья'],
        'entertainment' => ['игры', 'сериалы', 'ютуб', 'стримы', 'мероприятия'],
        'technology' => ['гаджеты', 'программы', 'искуственный интеллект', 'роботы', 'технологии']
    ];

    public function detectTopicShift(string $currentMessage, array $recentHistory): bool
    {
        $message = mb_strtolower($currentMessage);

        $lastThreeMessage = array_slice($recentHistory, -3);
        $isAssist = 0;

        foreach ($lastThreeMessage as $mess) {
            if ($mess['role'] === Message::ASSISTANT_ROLE) {
                $isAssist++;
            }
        }
        if ($isAssist >= 3) {
            return true;
        }

        // Детектим явные указания на смену деятельности
        $shiftPatterns = [
            '/занят.*другим/i',
            '/сейчас не.*(играю|занимаюсь)/i',
            '/перестал.*(играть|заниматься)/i',
            '/уже не.*(интересуюсь|увлекаюсь)/i',
            '/начал.*новое/i',
            '/появилось.*новое/i',
            '/мечта.*(научиться|попробовать)/i',
            '/хочу.*(изучить|начать)/i'
        ];

        foreach ($shiftPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        // Если одна тема доминирует в истории - пора менять
        $recentTopics = $this->extractTopics(array_merge($recentHistory, [['content' => $currentMessage]]));
        if (count($recentTopics) === 1) {
            return true;
        }

        // Если пользователь дает короткие/незаинтересованные ответы
        $lastUserMessages = array_filter(
            array_slice($recentHistory, -3),
            fn($msg) => isset($msg['role']) && $msg['role'] === 'user'
        );

        if (count($lastUserMessages) >= 2) {
            $avgLength = array_sum(array_map(fn($msg) => strlen($msg['content'] ?? ''), $lastUserMessages)) / count($lastUserMessages);
            if ($avgLength < 30) { // Короткие ответы = потеря интереса
                return true;
            }
        }

        return false;
    }

    public function extractTopics(array $messages): array
    {
        $topics = [];
        $topicPatterns = [
            'games' => '/\b(игр|гейм|steam|playstation|xbox|танк|зомби|minecraft|gta|project zomboid)\w*\b/iu',
            'music' => '/\b(музык|трек|бит|альбом|гитар|пианино|концерт|плейлист|рэп|хип-хоп)\w*\b/iu',
            'programming' => '/\b(код|программир|фреймворк|php|symfony|laravel|javascript|python)\w*\b/iu',
            'work' => '/\b(работ|проект|задач|дедлайн|коллег|офис|карьер)\w*\b/iu',
            'learning' => '/\b(учу|изуча|курс|урок|язык|навык|образован)\w*\b/iu',
            'hobbies' => '/\b(хобби|увлечен|рисован|фотограф|готовк|рукодел)\w*\b/iu',
            'sports' => '/\b(спорт|футбол|хоккей|баскетбол|тренировк|зал|бег)\w*\b/iu'
        ];

        foreach ($messages as $message) {
            $content = $message['content'] ?? '';

            foreach ($topicPatterns as $topic => $pattern) {
                if (preg_match_all($pattern, mb_strtolower($content), $matches)) {
                    $topics[$topic] = ($topics[$topic] ?? 0) + count($matches[0]);
                }
            }

            // Детектим смену деятельности
            if (preg_match('/\b(занят|увлекаюсь|интересу|изучаю|учусь|начал)\s+([^.!?]+)/iu', $content, $matches)) {
                $activity = trim($matches[2]);
                if (!empty($activity) && strlen($activity) > 3) {
                    $topics["current_activity:{$activity}"] = 10;
                }
            }
        }

        // Фильтруем и сортируем
        $topics = array_filter($topics, fn($score) => $score > 0);
        arsort($topics);

        return array_keys(array_slice($topics, 0, 5));
    }

    public function getFreshTopic(array $recentTopics): string
    {
        // Извлекаем категории из недавних тем
        $usedCategories = [];
        foreach ($recentTopics as $topic) {
            foreach (array_keys(self::TOPIC_CATEGORIES) as $category) {
                if (str_contains($topic, $category)) {
                    $usedCategories[] = $category;
                }
            }
        }

        // Исключаем недавно использованные категории
        $availableCategories = array_diff(
            array_keys(self::TOPIC_CATEGORIES),
            array_slice(array_unique($usedCategories), -2) // последние 2 категории
        );

        if (empty($availableCategories)) {
            $availableCategories = array_keys(self::TOPIC_CATEGORIES);
        }

        // Выбираем случайную категорию и тему
        $randomCategory = $availableCategories[array_rand($availableCategories)];
        $topics = self::TOPIC_CATEGORIES[$randomCategory];

        return $topics[array_rand($topics)];
    }
}
