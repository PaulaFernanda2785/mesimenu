<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PublicInteractionRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare("
            INSERT INTO public_interactions (
                visitor_name,
                visitor_email,
                message,
                status,
                source_page,
                submitted_ip,
                user_agent,
                created_at,
                updated_at
            ) VALUES (
                :visitor_name,
                :visitor_email,
                :message,
                'pending',
                :source_page,
                :submitted_ip,
                :user_agent,
                NOW(),
                NOW()
            )
        ");

        $stmt->execute([
            'visitor_name' => $data['visitor_name'],
            'visitor_email' => $data['visitor_email'],
            'message' => $data['message'],
            'source_page' => $data['source_page'] ?? null,
            'submitted_ip' => $data['submitted_ip'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function listPublished(int $limit = 6): array
    {
        $limit = max(1, min(24, $limit));

        $stmt = $this->db()->prepare("
            SELECT
                id,
                visitor_name,
                message,
                published_at,
                created_at
            FROM public_interactions
            WHERE status = 'published'
            ORDER BY COALESCE(published_at, updated_at, created_at) DESC, id DESC
            LIMIT {$limit}
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listPaginated(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(20, $perPage));

        ['where_sql' => $whereSql, 'params' => $params] = $this->buildWhereClause($filters);

        $countStmt = $this->db()->prepare("
            SELECT COUNT(*)
            FROM public_interactions pi
            WHERE {$whereSql}
        ");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetchColumn() ?: 0);

        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $offset = ($page - 1) * $perPage;

        $listStmt = $this->db()->prepare("
            SELECT
                pi.id,
                pi.visitor_name,
                pi.visitor_email,
                pi.message,
                pi.status,
                pi.source_page,
                pi.submitted_ip,
                pi.user_agent,
                pi.reviewed_by_user_id,
                pi.reviewed_at,
                pi.published_at,
                pi.created_at,
                pi.updated_at,
                u.name AS reviewed_by_user_name
            FROM public_interactions pi
            LEFT JOIN users u
                ON u.id = pi.reviewed_by_user_id
            WHERE {$whereSql}
            ORDER BY
                CASE pi.status
                    WHEN 'pending' THEN 1
                    WHEN 'published' THEN 2
                    WHEN 'rejected' THEN 3
                    ELSE 9
                END,
                COALESCE(pi.published_at, pi.updated_at, pi.created_at) DESC,
                pi.id DESC
            LIMIT {$perPage}
            OFFSET {$offset}
        ");
        $listStmt->execute($params);

        return [
            'items' => $listStmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    public function metrics(array $filters = []): array
    {
        ['where_sql' => $whereSql, 'params' => $params] = $this->buildWhereClause($filters);

        $stmt = $this->db()->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN pi.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN pi.status = 'published' THEN 1 ELSE 0 END) AS published_count,
                SUM(CASE WHEN pi.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                MAX(pi.created_at) AS last_created_at
            FROM public_interactions pi
            WHERE {$whereSql}
        ");
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending_count' => (int) ($row['pending_count'] ?? 0),
            'published_count' => (int) ($row['published_count'] ?? 0),
            'rejected_count' => (int) ($row['rejected_count'] ?? 0),
            'last_created_at' => (string) ($row['last_created_at'] ?? ''),
        ];
    }

    public function findById(int $interactionId): ?array
    {
        $stmt = $this->db()->prepare("
            SELECT
                pi.id,
                pi.visitor_name,
                pi.visitor_email,
                pi.message,
                pi.status,
                pi.source_page,
                pi.submitted_ip,
                pi.user_agent,
                pi.reviewed_by_user_id,
                pi.reviewed_at,
                pi.published_at,
                pi.created_at,
                pi.updated_at,
                u.name AS reviewed_by_user_name
            FROM public_interactions pi
            LEFT JOIN users u
                ON u.id = pi.reviewed_by_user_id
            WHERE pi.id = :interaction_id
            LIMIT 1
        ");
        $stmt->execute(['interaction_id' => $interactionId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateModeration(int $interactionId, array $data): void
    {
        $stmt = $this->db()->prepare("
            UPDATE public_interactions
            SET visitor_name = :visitor_name,
                visitor_email = :visitor_email,
                message = :message,
                status = :status,
                reviewed_by_user_id = :reviewed_by_user_id,
                reviewed_at = :reviewed_at,
                published_at = :published_at,
                updated_at = NOW()
            WHERE id = :interaction_id
            LIMIT 1
        ");

        $stmt->bindValue(':interaction_id', $interactionId, PDO::PARAM_INT);
        $stmt->bindValue(':visitor_name', (string) $data['visitor_name'], PDO::PARAM_STR);
        $stmt->bindValue(':visitor_email', (string) $data['visitor_email'], PDO::PARAM_STR);
        $stmt->bindValue(':message', (string) $data['message'], PDO::PARAM_STR);
        $stmt->bindValue(':status', (string) $data['status'], PDO::PARAM_STR);

        $reviewedByUserId = isset($data['reviewed_by_user_id']) ? (int) $data['reviewed_by_user_id'] : 0;
        if ($reviewedByUserId > 0) {
            $stmt->bindValue(':reviewed_by_user_id', $reviewedByUserId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':reviewed_by_user_id', null, PDO::PARAM_NULL);
        }

        $reviewedAt = trim((string) ($data['reviewed_at'] ?? ''));
        if ($reviewedAt !== '') {
            $stmt->bindValue(':reviewed_at', $reviewedAt, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':reviewed_at', null, PDO::PARAM_NULL);
        }

        $publishedAt = trim((string) ($data['published_at'] ?? ''));
        if ($publishedAt !== '') {
            $stmt->bindValue(':published_at', $publishedAt, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':published_at', null, PDO::PARAM_NULL);
        }

        $stmt->execute();
    }

    public function delete(int $interactionId): void
    {
        $stmt = $this->db()->prepare("
            DELETE FROM public_interactions
            WHERE id = :interaction_id
            LIMIT 1
        ");
        $stmt->execute(['interaction_id' => $interactionId]);
    }

    private function buildWhereClause(array $filters): array
    {
        $status = strtolower(trim((string) ($filters['status'] ?? '')));
        $search = trim((string) ($filters['search'] ?? ''));

        $where = ['1 = 1'];
        $params = [];

        if ($status !== '') {
            $where[] = 'pi.status = :status';
            $params['status'] = $status;
        }

        if ($search !== '') {
            $where[] = "(
                LOWER(COALESCE(pi.visitor_name, '')) LIKE :search
                OR LOWER(COALESCE(pi.visitor_email, '')) LIKE :search
                OR LOWER(COALESCE(pi.message, '')) LIKE :search
                OR CAST(pi.id AS CHAR) = :id_search
            )";
            $params['search'] = '%' . strtolower($search) . '%';
            $params['id_search'] = $search;
        }

        return [
            'where_sql' => implode(' AND ', $where),
            'params' => $params,
        ];
    }
}
