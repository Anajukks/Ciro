<?php

// CONSTANTES

// Opções de menus
const OPCAO_SAIR = '0';
const OPCAO_CRIAR_CACHORRO = '1';
const OPCAO_APAGAR_CACHORRO = '2';
const OPCAO_ATUALIZAR_CACHORRO = '3';


// CLASSE CACHORRO

class Cachorro {
    public function __construct(
        public string $nome,
        public int $idade,
        public float $peso,
        public bool $vacinado
    ) {}

    public function toArray(): array {
        return [
            'nome' => $this->nome,
            'idade' => $this->idade,
            'peso' => $this->peso,
            'vacinado' => $this->vacinado,
        ];
    }
}

// FUNÇÕES

function menu_principal(string $uri_servico_dados) {
    global $mensagem;

    $cachorros = req_GET_cachorros($uri_servico_dados);

    echo "\n=============== CADASTRANDO O CACHORRO NO PETSHOP ===============\n";
    if ($cachorros != NULL) {
        foreach($cachorros as $n){
            echo "Nome do cachorro {$n->nome} (Chave: {$n->_id}):\n";
            echo "Idade: {$n->idade} anos\n";
            echo "Peso: {$n->peso} kg\n";
            echo "Vacinado: " . ($n->vacinado ? 'Sim' : 'Não') . "\n\n";
        }
    }
    echo "----------------------------------------------\n";
    if (strlen($mensagem) > 0) {
        echo "$mensagem\n";
        echo "----------------------------------------------\n";
    }
    $mensagem = '';
    echo OPCAO_CRIAR_CACHORRO . " - Cadastrar cachorro\n";
    echo OPCAO_APAGAR_CACHORRO . " - Excluir cachorro\n";
    echo OPCAO_ATUALIZAR_CACHORRO . " - Atualizar cachorro\n";
    echo OPCAO_SAIR . " - Sair\n";
    echo "Digite sua opção: ";
    $opcao = readline();
    return $opcao;
}

function menu_criar(string $uri_servico_dados) {
    global $mensagem;

    echo "Digite o nome do cachorro:\n";
    $nome = readline();

    echo "Digite a idade do cachorro:\n";
    $idade = (int) readline();

    echo "Digite o peso do cachorro:\n";
    $peso = (float) readline();

    echo "O cachorro está vacinado? (1 para Sim, 0 para Não):\n";
    $vacinado = (bool) readline();

    $cachorro = new Cachorro($nome, $idade, $peso, $vacinado);

    $resp = req_POST_cachorros($cachorro, $uri_servico_dados);

    if ($resp['codigo'] != 201) {
        $mensagem = "Erro {$resp['codigo']} ao cadastrar cachorro.\n" . $resp["corpo"];
    } else {
        var_dump($resp['corpo']);
    }
}

function menu_apagar(string $uri_servico_dados) {
    global $mensagem;

    echo "Digite a chave do cachorro que deseja apagar: ";
    $chave = readline();

    $resp = req_DELETE_cachorros($chave, $uri_servico_dados);

    if ($resp['codigo'] != 200) {
        $mensagem = "Erro {$resp['codigo']} ao apagar cachorro $chave.\n" .
                    $resp["corpo"];
    }
}

function menu_atualizar(string $uri_servico_dados) {
    global $mensagem;

    echo "Digite a chave do cachorro que deseja atualizar: ";
    $chave = readline();

    echo "Digite o novo nome do cachorro:\n";
    $nome = readline();

    echo "Digite a nova idade do cachorro:\n";
    $idade = (int) readline();

    echo "Digite o novo peso do cachorro:\n";
    $peso = (float) readline();

    echo "O cachorro está vacinado? (1 para Sim, 0 para Não):\n";
    $vacinado = (bool) readline();

    $cachorro = new Cachorro($nome, $idade, $peso, $vacinado);

    $resp = req_PUT_cachorro($chave, $cachorro, $uri_servico_dados);

    if ($resp['codigo'] != 200) {
        $mensagem = "Erro {$resp['codigo']} ao atualizar cachorro $chave.\n" . $resp["corpo"];
    }
}

// Requisições

function enviar_requisicao(string $uri, string $metodo = 'GET', string $corpo = '',
    array $cabecalhos = [], array $curl_options = []): array  {
    $resposta = [];

    $ch = curl_init($uri);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $cabecalhos);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
    curl_setopt_array($ch, $curl_options);

    $str_resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $resposta['codigo'] = $info['http_code'];
    $str_cabecalhos = substr($str_resp, 0, $info['header_size']);
    $linhas = explode('\n', $str_cabecalhos);
    $resposta['cabecalhos'] = array_slice($linhas, 1);
    $resposta['corpo'] = substr($str_resp, $info['header_size']);
    $resposta['erro'] = curl_error($ch);
    curl_close($ch);

    return $resposta;
}

function req_GET_cachorros(string $uri_servico_dados): array {
    $resp = enviar_requisicao("$uri_servico_dados/bancos/cachorros");
    if ($resp['codigo'] != 200) {
        echo "Erro obtendo cachorros.\n";
        echo "Erro {$resp['codigo']}: {$resp['corpo']}";
        exit(1);
    }
    $cachorros = json_decode($resp['corpo']);
    return $cachorros;
}

function req_DELETE_cachorros(string $chave, string $uri_servico_dados): array {
    return enviar_requisicao("$uri_servico_dados/bancos/cachorros/$chave",
        metodo: 'DELETE'
    );
}

function req_PUT_cachorro(string $chave, Cachorro $cachorro, string $uri_servico_dados): array {
    return enviar_requisicao(
        "$uri_servico_dados/bancos/cachorros/$chave",
        metodo: 'PUT',
        cabecalhos: ['Content-Type: application/json'],
        corpo: json_encode($cachorro->toArray())
    );
}

function req_HEAD_banco_cachorros(string $uri_servico_dados): array {
    return enviar_requisicao(
        "$uri_servico_dados/bancos/cachorros",
        metodo: 'HEAD'
    );
}

function req_PUT_banco_cachorros(string $uri_servico_dados): array {
    return enviar_requisicao(
        "$uri_servico_dados/bancos/cachorros",
        metodo: 'PUT',
        cabecalhos: ['Content-Type: application/json'],
        corpo: json_encode([
            'usuario' => 'lista_de_cachorros',
            'nome' => 'cachorros'
        ])
    );
}

function req_POST_cachorros(Cachorro $cachorro, string $uri_servico_dados): array {
    return enviar_requisicao(
        "$uri_servico_dados/bancos/cachorros",
        metodo: 'POST',
        cabecalhos: ['Content-Type: application/json'],
        corpo: json_encode($cachorro->toArray())
    );
}

// PROGRAMA PRINCIPAL

if (sizeof($argv) != 2) {
    echo "Uso: php {$argv[0]} <endereco-servico-dados>\n";
    exit(1);
}

$uri_servico_dados = $argv[1];
$mensagem = '';

$resp = req_HEAD_banco_cachorros($uri_servico_dados);
if ($resp['codigo'] == 404) {
    $resp = req_PUT_banco_cachorros($uri_servico_dados);
    if ($resp['codigo'] != 201) {
        echo "Erro ao criar banco de cachorros.\n";
        echo "Erro {$resp['codigo']}: {$resp['corpo']}\n";
        exit(1);
    }
}

do {
    $opcao = menu_principal($uri_servico_dados);
    switch ($opcao) {
        case OPCAO_CRIAR_CACHORRO:
            menu_criar($uri_servico_dados);
            break;
        case OPCAO_APAGAR_CACHORRO:
            menu_apagar($uri_servico_dados);
            break;
        case OPCAO_ATUALIZAR_CACHORRO:
            menu_atualizar($uri_servico_dados);
            break;
        case OPCAO_SAIR:
            echo "Saindo...\n";
            break;
        default:
            echo "Opção inválida!\n";
    }
} while ($opcao != OPCAO_SAIR);

?>
