<?php
declare(strict_types=1);

/** #40 Template Versioning Manager */
class TemplateVersioningService {
    
    private PDO $db;
    
    public function __construct() { $this->db = Database::getInstance(); }

    public function createVersion(string $type, string $content, int $createdBy): array {
        // Deactivate old
        $this->db->prepare("UPDATE job_templates SET is_active=0 WHERE type=:t AND is_active=1")->execute(["t"=>$type]);
        // Get next version
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(version),0)+1 as nv FROM job_templates WHERE type=:t"); $stmt->execute(["t"=>$type]);
        $version = (int)$stmt->fetch()["nv"];
        $this->db->prepare("INSERT INTO job_templates (type,content,version,is_active,created_by) VALUES(:t,:c,:v,1,:cb)")
            ->execute(["t"=>$type,"c"=>$content,"v"=>$version,"cb"=>$createdBy]);
        return ["id"=>(int)$this->db->lastInsertId(),"version"=>$version];
    }

    public function getActive(string $type): ?array {
        $stmt = $this->db->prepare("SELECT * FROM job_templates WHERE type=:t AND is_active=1 ORDER BY version DESC LIMIT 1");
        $stmt->execute(["t"=>$type]); $r = $stmt->fetch(); return $r ?: null;
    }
}
