<?php

namespace Source\Correios;

use ReflectionClass;

class CorreiosWS
{

    private $nCdEmpresa;
    private $sDsSenha;
    private $nCdServico;
    private $sCepOrigem;
    private $sCepDestino;
    private $sCdMaoPropria;
    private $nVlValorDeclarado;
    private $sCdAvisoRecebimento;
    private $nVlPeso;
    private $nCdFormato;
    private $nVlComprimento;
    private $nVlAltura;
    private $nVlLargura;
    private $nVlDiametro;

    private $retornoConsulta;
    private $erro;

    private static $urlCorreios;

    /**
     * __construct
     * @return void
     */
    public function __construct()
    {
        self::$urlCorreios = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx?wsdl';
        $this->retornoConsulta = null;
        $this->erro = null;
    }

    /**
     * boot
     *
     * @param string $nCdServico
     * @param string $sCepOrigem
     * @param string $sCepDestino
     * @param string $nVlPeso
     * @param integer $nCdFormato
     * @param float $nVlComprimento
     * @param float $nVlAltura
     * @param float $nVlLargura
     * @param float $nVlDiametro
     * @param string $nCdEmpresa
     * @param string $sDsSenha
     * @param string $sCdMaoPropria
     * @param float $nVlValorDeclarado
     * @param string $sCdAvisoRecebimento
     * @return CorreiosWS
     */
    public function boot(
        string $nCdServico,
        string $sCepOrigem,
        string $sCepDestino,
        string $nVlPeso,
        int $nCdFormato,
        float $nVlComprimento,
        float $nVlAltura,
        float $nVlLargura,
        float $nVlDiametro,
        string $nCdEmpresa = '',
        string $sDsSenha = '',
        string $sCdMaoPropria = 'N',
        float $nVlValorDeclarado = 0,
        string $sCdAvisoRecebimento = 'N'
    ): CorreiosWS {

        $this->nCdServico = filter_var($nCdServico, FILTER_SANITIZE_STRING);
        $this->sCepOrigem = filtroCep(filter_var($sCepOrigem, FILTER_SANITIZE_STRING));
        $this->sCepDestino = filtroCep(filter_var($sCepDestino, FILTER_SANITIZE_STRING));
        $this->sCdMaoPropria = filter_var($sCdMaoPropria, FILTER_SANITIZE_STRING);
        $this->nVlValorDeclarado = (float) filter_var($nVlValorDeclarado, FILTER_SANITIZE_NUMBER_FLOAT);
        $this->sCdAvisoRecebimento = filter_var($sCdAvisoRecebimento, FILTER_SANITIZE_STRING);
        $this->nVlPeso = filter_var($nVlPeso, FILTER_SANITIZE_STRING);
        $this->nVlPeso = (float) str_replace(',', '.', $this->nVlPeso);
        $this->nCdFormato = (int) filter_var($nCdFormato, FILTER_SANITIZE_NUMBER_INT);
        $this->nVlComprimento = (float) filter_var($nVlComprimento, FILTER_SANITIZE_NUMBER_FLOAT);
        $this->nVlAltura = (float) filter_var($nVlAltura, FILTER_SANITIZE_NUMBER_FLOAT);
        $this->nVlLargura = (float) filter_var($nVlLargura, FILTER_SANITIZE_NUMBER_FLOAT);
        $this->nVlDiametro = (float) filter_var($nVlDiametro, FILTER_SANITIZE_NUMBER_FLOAT);
        $this->nCdEmpresa = filter_var($nCdEmpresa, FILTER_SANITIZE_STRING);
        $this->sDsSenha = filter_var($sDsSenha, FILTER_SANITIZE_STRING);

        // Valida algumas informações importantes antes de enviar a requisição
        $this->validaParametros();

        return $this;
    }

    /**
     * validaParametros
     *
     * @return void
     */
    private function validaParametros(): void
    {

        try {

            // Caso seja informado um cód. de serviço inválido enquanto o usuário não informe
            // o código da empresa e senha referentes ao contrato com o Correios
            if (
                empty($this->nCdEmpresa) && !in_array(
                    $this->nCdServico,
                    ['04014', '04510', '04782', '04790', '04804']
                )
            ) {
                throw new \Exception('app1');
            }

            // Caso seja informado o formato da encomenda (Formato rolo/prisma)
            // é obrigatório informar o diamêtro
            if ($this->nCdFormato == 2 && $this->nVlDiametro == 0) {
                throw new \Exception('app2');
            }

            // Tratando formato envelope
            if ($this->nCdFormato == 3) {

                if ($this->nVlPeso > 1) {
                    throw new \Exception('app3');
                }
                $this->nVlAltura = 0;
            }

            if (!$this->sCepOrigem) {
                throw new \Exception('app4');
            }

            if (!$this->sCepDestino) {
                throw new \Exception('app5');
            }
        } catch (\Exception $e) {

            $this->erro = new \stdClass();
            $this->erro->cod = $e->getMessage();
            $this->erro->msg = 'Erro na validação dos dados';
            $this->erro->msg_tratada = textError($e->getMessage());
        }
    }

    /**
     * calcPrecoPrazo
     *
     * @return CorreiosWS
     */
    public function calcPrecoPrazo(): CorreiosWS
    {

        if ($this->erro === null) {

            $dados = (array) get_object_vars($this);

            $soap = new \SoapClient(self::$urlCorreios);
            $res = $soap->CalcPrecoPrazo($dados);

            if ($res->CalcPrecoPrazoResult->Servicos->cServico->Erro != '0') {

                $this->retornoConsulta = null;

                $this->erro = new \stdClass();
                $this->erro->cod = $res->CalcPrecoPrazoResult->Servicos->cServico->Erro;
                $this->erro->msg = $res->CalcPrecoPrazoResult->Servicos->cServico->MsgErro;
                $this->erro->msg_tratada = textError($this->erro->cod);
            } else {

                $this->retornoConsulta = $res->CalcPrecoPrazoResult->Servicos->cServico;
                $this->erro = null;
            }
        }

        return $this;
    }

    /**
     * toJson
     *
     * @return string
     */
    public function toJson(): string
    {
        if ($this->erro !== null) {
            return json_encode($this->erro);
        }

        return json_encode($this->retornoConsulta);
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray(): array
    {
        if ($this->erro !== null) {
            return (array) $this->erro;
        }

        return (array) $this->retornoConsulta;
    }
}
