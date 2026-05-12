<?php
declare(strict_types=1);
/**
 * #3 Job Requisition Approval Workflow — Chain of Responsibility.
 */
interface ApproverHandler {
    public function setNext(ApproverHandler $next): ApproverHandler;
    public function handle(array &$chain, int $approverId, string $level): bool;
}

abstract class AbstractApprover implements ApproverHandler {
    private ?ApproverHandler $next = null;
    abstract protected function getLevel(): string;

    public function setNext(ApproverHandler $next): ApproverHandler {
        $this->next = $next; return $next;
    }
    public function handle(array &$chain, int $approverId, string $level): bool {
        if ($level === $this->getLevel()) {
            foreach ($chain as &$step) {
                if ($step["level"] === $level) { $step["status"] = "approved"; $step["approved_by"] = $approverId; $step["approved_at"] = date("Y-m-d H:i:s"); }
            }
            return true;
        }
        return $this->next ? $this->next->handle($chain, $approverId, $level) : false;
    }
}
class DepartmentHeadApprover extends AbstractApprover { protected function getLevel(): string { return "department_head"; } }
class HRDirectorApprover extends AbstractApprover { protected function getLevel(): string { return "hr_director"; } }
class FinanceApprover extends AbstractApprover { protected function getLevel(): string { return "finance"; } }

class ApprovalWorkflowService {
    private JobRequisitionModel $jobModel;
    private EventBus $eventBus;
    private AuditLogger $audit;

    public function __construct() {
        $this->jobModel = new JobRequisitionModel();
        $this->eventBus = EventBus::getInstance();
        $this->audit = AuditLogger::getInstance();
    }

    public function submitForApproval(int $jobId): void {
        $job = $this->jobModel->findById($jobId);
        if (!$job) throw new \DomainException("Job not found");
        $chain = [
            ["level" => "department_head", "status" => "pending"],
            ["level" => "hr_director", "status" => "pending"],
            ["level" => "finance", "status" => "pending"],
        ];
        $this->jobModel->update($jobId, ["status" => "pending_approval", "approval_chain_json" => json_encode($chain)]);
        $this->audit->log(null, "job_requisition", $jobId, "submitted_for_approval", [], ["status" => "pending_approval"]);
    }

    public function approve(int $jobId, int $approverId, string $level): bool {
        $job = $this->jobModel->findById($jobId);
        if (!$job) return false;
        $chain = json_decode($job["approval_chain_json"] ?? "[]", true);

        $head = new DepartmentHeadApprover();
        $hr = new HRDirectorApprover();
        $fin = new FinanceApprover();
        $head->setNext($hr)->setNext($fin);
        $result = $head->handle($chain, $approverId, $level);

        if ($result) {
            $allApproved = !array_filter($chain, fn($s) => $s["status"] !== "approved");
            $newStatus = $allApproved ? "live" : "pending_approval";
            $this->jobModel->update($jobId, ["approval_chain_json" => json_encode($chain), "status" => $newStatus]);
            if ($allApproved) $this->eventBus->publish("JobApprovedEvent", ["job_id" => $jobId]);
            $this->audit->log($approverId, "job_requisition", $jobId, "approval_step", ["level" => $level], ["status" => $newStatus]);
        }
        return $result;
    }
}
