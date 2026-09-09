<?php

namespace App\Services;

use App\Config\Database;

class QueueService
{
    public static function getPendingQueues(int $limit = 10): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            "SELECT * FROM webhook_queue
             WHERE status IN ('pending', 'retry')
             ORDER BY id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function markSuccess(int $id): void
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            "UPDATE webhook_queue
             SET status = 'success', processed_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public static function markFailed(int $id, string $errorMessage): void
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT retry_count FROM webhook_queue WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return;
        }

        $retryCount = (int)($row['retry_count'] ?? 0) + 1;
        $maxRetry = (int)($_ENV['MAX_RETRY'] ?? 5);

        if ($retryCount >= $maxRetry) {
            $stmt = $pdo->prepare(
                "UPDATE webhook_queue
                 SET status = 'failed', retry_count = ?, error_message = ?, processed_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$retryCount, $errorMessage, $id]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE webhook_queue
                 SET status = 'retry', retry_count = ?, error_message = ?
                 WHERE id = ?"
            );
            $stmt->execute([$retryCount, $errorMessage, $id]);
        }
    }
}
