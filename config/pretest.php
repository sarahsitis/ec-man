<?php

return [
    'version' => 'ec-pretest-v1',
    'domains' => ['reading' => 'Pemahaman bacaan', 'language_use' => 'Penggunaan bahasa'],
    'skills' => [
        'listening' => 'Saya memahami gagasan utama saat mendengarkan percakapan bahasa Inggris.',
        'speaking' => 'Saya menyampaikan ide dan menjawab pertanyaan dalam bahasa Inggris.',
        'reading' => 'Saya memahami gagasan utama dan informasi penting dari bacaan bahasa Inggris.',
        'writing' => 'Saya menulis paragraf sederhana yang runtut dalam bahasa Inggris.',
    ],
    'scales' => [
        1 => 'Belum bisa / membutuhkan banyak bantuan',
        2 => 'Bisa dengan bimbingan',
        3 => 'Bisa secara mandiri',
        4 => 'Bisa secara konsisten pada tugas yang lebih menantang',
    ],
    // Keep answer keys on the server. Change the version when revising the questions.
    'questions' => [
        [
            'id' => 'r1', 'domain' => 'reading',
            'passage' => 'Maya joins the English Club every Friday after school. She enjoys telling stories and wants to speak more confidently.',
            'prompt' => 'When does Maya join the English Club?',
            'options' => ['a' => 'Every Monday morning', 'b' => 'Every Friday after school', 'c' => 'Every Saturday evening', 'd' => 'Every day before school'],
            'correct' => 'b',
        ],
        [
            'id' => 'r2', 'domain' => 'reading',
            'passage' => 'Maya joins the English Club every Friday after school. She enjoys telling stories and wants to speak more confidently.',
            'prompt' => 'Why does Maya join the club?',
            'options' => ['a' => 'To learn to cook', 'b' => 'To practise basketball', 'c' => 'To become more confident in speaking', 'd' => 'To study mathematics'],
            'correct' => 'c',
        ],
        [
            'id' => 'r3', 'domain' => 'reading',
            'passage' => 'Notice: The debate practice has moved from Room 2 to the library. It starts at 3 p.m. Please bring your notes and arrive ten minutes early.',
            'prompt' => 'Where will the debate practice take place?',
            'options' => ['a' => 'In the library', 'b' => 'In Room 2', 'c' => 'In the school hall', 'd' => 'In the playground'],
            'correct' => 'a',
        ],
        [
            'id' => 'r4', 'domain' => 'reading',
            'passage' => 'Notice: The debate practice has moved from Room 2 to the library. It starts at 3 p.m. Please bring your notes and arrive ten minutes early.',
            'prompt' => 'What time should participants arrive?',
            'options' => ['a' => '3:10 p.m.', 'b' => '3:00 p.m.', 'c' => '2:30 p.m.', 'd' => '2:50 p.m.'],
            'correct' => 'd',
        ],
        [
            'id' => 'r5', 'domain' => 'reading',
            'passage' => 'Rafi used to avoid speaking English because he was afraid of mistakes. After practising with a partner each week, he began asking questions in class. He still makes mistakes, but now he sees them as part of learning.',
            'prompt' => 'What is the main idea of the passage?',
            'options' => ['a' => 'Making mistakes means a student cannot learn', 'b' => 'Regular practice helped Rafi become more confident', 'c' => 'Rafi stopped attending English classes', 'd' => 'Working alone is always better than working with a partner'],
            'correct' => 'b',
        ],
        [
            'id' => 'r6', 'domain' => 'reading',
            'passage' => 'Rafi used to avoid speaking English because he was afraid of mistakes. After practising with a partner each week, he began asking questions in class. He still makes mistakes, but now he sees them as part of learning.',
            'prompt' => 'What can we infer about Rafi now?',
            'options' => ['a' => 'He never makes mistakes', 'b' => 'He dislikes practising with others', 'c' => 'He is more willing to try even when he may make mistakes', 'd' => 'He has become an English teacher'],
            'correct' => 'c',
        ],
        [
            'id' => 'u1', 'domain' => 'language_use', 'passage' => null,
            'prompt' => 'Complete the sentence: She ___ English every day.',
            'options' => ['a' => 'study', 'b' => 'studying', 'c' => 'studied', 'd' => 'studies'], 'correct' => 'd',
        ],
        [
            'id' => 'u2', 'domain' => 'language_use', 'passage' => null,
            'prompt' => 'Choose the correct response: "Thank you for helping me."',
            'options' => ['a' => 'You are welcome.', 'b' => 'I am fifteen.', 'c' => 'It is on the table.', 'd' => 'See you yesterday.'], 'correct' => 'a',
        ],
        [
            'id' => 'u3', 'domain' => 'language_use', 'passage' => null,
            'prompt' => 'Complete the sentence: Yesterday, we ___ a short story together.',
            'options' => ['a' => 'write', 'b' => 'wrote', 'c' => 'writing', 'd' => 'writes'], 'correct' => 'b',
        ],
        [
            'id' => 'u4', 'domain' => 'language_use', 'passage' => null,
            'prompt' => 'Choose the word closest in meaning to "improve" in "I want to improve my English."',
            'options' => ['a' => 'forget', 'b' => 'stop', 'c' => 'make better', 'd' => 'hide'], 'correct' => 'c',
        ],
        [
            'id' => 'u5', 'domain' => 'language_use', 'passage' => null,
            'prompt' => 'Complete the sentence: I was nervous, ___ I decided to try speaking.',
            'options' => ['a' => 'because', 'b' => 'so that', 'c' => 'or', 'd' => 'but'], 'correct' => 'd',
        ],
        [
            'id' => 'u6', 'domain' => 'language_use', 'passage' => null,
            'prompt' => 'Choose the sentence that politely asks someone to repeat a question.',
            'options' => ['a' => 'Could you say that again, please?', 'b' => 'You never ask questions.', 'c' => 'I repeated it yesterday.', 'd' => 'The question is on Friday.'], 'correct' => 'a',
        ],
    ],
];
