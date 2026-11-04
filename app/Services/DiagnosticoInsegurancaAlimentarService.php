<?php

namespace App\Services;

use App\Models\QuestionarioInsegurancaAlimentar;

class DiagnosticoInsegurancaAlimentarService
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array{pontuacao_total: int, diagnostico: string, observacoes_diagnostico: string}
     */
    public function calculate(array $answers): array
    {
        $score = 0;

        $score += match ((string) ($answers['refeicoes_semana'] ?? '')) {
            '7 vezes' => 0,
            '5 vezes', '6 vezes' => 1,
            '3 vezes', '4 vezes' => 2,
            '1 vez', '2 vezes' => 3,
            default => 0,
        };

        $score += match ((string) ($answers['refeicoes_dia'] ?? '')) {
            '3 vezes ao dia', '4 vezes ao dia', '5 vezes ou mais' => 0,
            '2 vezes ao dia' => 1,
            '1 vez ao dia' => 2,
            'Nenhuma' => 4,
            default => 0,
        };

        $score += match ((string) ($answers['preocupacao_falta_comida'] ?? '')) {
            'Nunca' => 0,
            'Raramente', 'Apenas 1 vez ao mes' => 1,
            'Semanalmente' => 2,
            'Diariamente' => 3,
            default => 0,
        };

        $score += match ((string) ($answers['reduziu_porcoes'] ?? '')) {
            'Nao' => 0,
            'Sim' => 2,
            'Frequentemente' => 4,
            default => 0,
        };

        $fatoresLimitantes = $this->asArray($answers['fatores_limitantes'] ?? []);
        $score += count($fatoresLimitantes);
        if (in_array('Falta de dinheiro', $fatoresLimitantes, true)) {
            $score++;
        }

        $programasAssistencia = $this->asArray($answers['programas_assistencia'] ?? []);
        $score += min(count($programasAssistencia), 2);

        $score += ((string) ($answers['alimentos_acessiveis'] ?? '') === 'Nao') ? 2 : 0;

        $score += match ((string) ($answers['frequencia_alimentos_baixo_custo'] ?? '')) {
            'Nunca' => 0,
            '1 vez', '2 vezes' => 1,
            '3 vezes', '4 vezes' => 2,
            'Acima de 4 vezes' => 3,
            default => 0,
        };

        $score += min(count($this->asArray($answers['alimentos_desejados'] ?? [])), 3);
        $score += ((string) ($answers['situacao_financeira_afeta'] ?? '') === 'Sim') ? 3 : 0;

        if ($score <= 6) {
            return [
                'pontuacao_total' => $score,
                'diagnostico' => QuestionarioInsegurancaAlimentar::DIAGNOSTICO_LEVE,
                'observacoes_diagnostico' => 'Familia classificada com Inseguranca Alimentar Leve devido a limitacoes ocasionais no acesso a alimentos nutritivos.',
            ];
        }

        if ($score <= 14) {
            return [
                'pontuacao_total' => $score,
                'diagnostico' => QuestionarioInsegurancaAlimentar::DIAGNOSTICO_MODERADA,
                'observacoes_diagnostico' => 'Familia classificada com Inseguranca Alimentar Moderada devido a preocupacao recorrente com falta de alimentos, limitacao financeira e reducao da qualidade alimentar.',
            ];
        }

        return [
            'pontuacao_total' => $score,
            'diagnostico' => QuestionarioInsegurancaAlimentar::DIAGNOSTICO_GRAVE,
            'observacoes_diagnostico' => 'Familia classificada com Inseguranca Alimentar Grave devido a privacao frequente de refeicoes adequadas, inseguranca alimentar constante e dependencia de recursos assistenciais.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function asArray(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, fn ($item): bool => filled($item))) : [];
    }
}
