<?php
declare(strict_types=1);
/** #11 Code-Execution Output Validator — Strategy per language */
interface CodeValidatorInterface {
    public function validate(string $candidateOutput, string $expectedOutput): array;
}

class PhpValidator implements CodeValidatorInterface {
    public function validate(string $candidateOutput, string $expectedOutput): array {
        $expected = array_filter(explode("\n", trim($expectedOutput)));
        $actual = array_filter(explode("\n", trim($candidateOutput)));
        $results = []; $passed = 0;
        foreach ($expected as $i => $exp) {
            $act = $actual[$i] ?? "";
            $pass = trim($act) === trim($exp);
            if ($pass) $passed++;
            $results[] = ["test" => $i+1, "passed" => $pass, "expected" => $exp, "actual" => $act];
        }
        $total = count($expected);
        return ["results" => $results, "passed" => $passed, "total" => $total, "score" => $total > 0 ? round(($passed/$total)*100,2) : 0];
    }
}
class PythonValidator implements CodeValidatorInterface {
    public function validate(string $co, string $eo): array { return (new PhpValidator())->validate($co, $eo); }
}
class JavaScriptValidator implements CodeValidatorInterface {
    public function validate(string $co, string $eo): array { return (new PhpValidator())->validate($co, $eo); }
}

class CodeValidatorService {
    public function validate(int $questionId, string $candidateOutput, string $language): array {
        $qModel = new QuestionModel(); $q = $qModel->findById($questionId);
        if (!$q || empty($q["expected_output"])) return ["score" => 0, "results" => []];
        $validators = ["php" => new PhpValidator(), "python" => new PythonValidator(), "javascript" => new JavaScriptValidator()];
        $validator = $validators[$language] ?? new PhpValidator();
        return $validator->validate($candidateOutput, $q["expected_output"]);
    }
}
