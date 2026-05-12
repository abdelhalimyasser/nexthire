<?php 

declare(strict_types=1);

class UserModel extends BaseModel {
   
    protected string $table = "users";

    public function authenticate(string $email, string $password): ?array {
        $stmt=$this->db->prepare("SELECT * FROM {$this->table} WHERE email=:e AND is_active=1 LIMIT 1");
        $stmt->execute(["e"=>$email]); $user=$stmt->fetch();
        if($user && password_verify($password,$user["password_hash"])) return $user;
        return null;
    }

    public function findByEmail(string $email): ?array {
        $stmt=$this->db->prepare("SELECT * FROM {$this->table} WHERE email=:e LIMIT 1");
        $stmt->execute(["e"=>$email]); $r=$stmt->fetch(); return $r?:null;
    }

    public function findByRole(string $role): array {
        $stmt=$this->db->prepare("SELECT * FROM {$this->table} WHERE role=:r AND is_active=1");
        $stmt->execute(["r"=>$role]); return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt=$this->db->prepare("SELECT * FROM {$this->table} WHERE id=:id LIMIT 1");
        $stmt->execute(["id"=>$id]); $r=$stmt->fetch(); return $r?:null;
    }

    public function anonymize(int $id): bool {
        return $this->update($id,["name"=>"[Anonymized]","email"=>"anon_".$id."@removed.local","password_hash"=>"","is_active"=>0]);
    }
}