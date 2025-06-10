<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class GitWebHookController extends Controller
{
    public function hooks(Request $request)
    {
        // Проверка секретного ключа
        $input_key = $request->input('key');
        $realKey = env('SECRET_KEY');

        if ($input_key !== $realKey) {
            return response()->json(['error' => 'wrong key'], 401);
        }

        // Логируем вызов вебхука
        Log::info("Webhook called", [
            "ip" => $request->ip(),
            "time" => now()->toDateTimeString()
        ]);

        // Укажите путь к git.exe на вашей системе (без кавычек!)
        $gitPath = 'C:/Program Files/Git/bin/git.exe'; // <-- ВАЖНО: убедитесь, что путь верен!

        // Рабочая директория — папка вашего проекта
        $projectDirectory = base_path(); // или задайте конкретный путь: 'C:/xampp/htdocs/your-project-folder'

        // Команды для выполнения
        $commands = [
            [$gitPath, 'checkout', 'main'],
            [$gitPath, 'reset', '--hard'],
            [$gitPath, 'pull', 'origin', 'main']
        ];

        foreach ($commands as $command) {
            $process = new Process($command);
            $process->setWorkingDirectory($projectDirectory); // Устанавливаем рабочую директорию
            $process->run();

            // Если команда не удалась — логируем и возвращаем ошибку
            if (!$process->isSuccessful()) {
                $errorMessage = $process->getErrorOutput();
                Log::error('Git command failed', [
                    'command' => implode(' ', $command),
                    'output' => $errorMessage
                ]);

                return response()->json(['error' => $errorMessage], 500);
            }

            // Логируем успешное выполнение команды
            Log::info('Git command executed successfully', [
                'command' => implode(' ', $command),
                'output' => $process->getOutput()
            ]);
        }

        return response()->json(['message' => 'Repository updated successfully'], 200);
    }
}