<?php

/*
|--------------------------------------------------------------------------
| Temas (assuntos)
|--------------------------------------------------------------------------
|
| Cada notícia recebe os temas cujas palavras-chave aparecem no título ou
| no resumo (comparação sem acentos e em minúsculas). A ORDEM define a
| prioridade: o primeiro tema encontrado é o que aparece no card.
| Cultura fica por último por ser o tema mais amplo.
|
| As expressões são regex aplicadas ao texto sem acentos; \b marca o
| limite de palavra. Evite termos genéricos (ex.: "apresenta").
|
*/

return [

    'obituarios' => [
        'name' => 'Obituários',
        'description' => 'Notas de falecimento e despedidas',
        'pattern' => '/\b(nota de falecimento|faleceu|falecimento|velorio|sepultamento|obituario|se despede de)\b/',
    ],

    'empregos' => [
        'name' => 'Empregos',
        'description' => 'Vagas do PAT, concursos e processos seletivos',
        'pattern' => '/\b(pat|vagas? de (emprego|trabalho|estagio)|concurso publico|abre concurso|processo seletivo|frente de trabalho)\b/',
    ],

    'policia' => [
        'name' => 'Polícia',
        'description' => 'Ocorrências policiais e segurança',
        'pattern' => '/\b(policia|policial|policiais|presos?|presas?|prisao|apreend\w*|furto|roubo|assalto|trafico|homicidio|assassinad\w*|gcm|guarda civil|delegacia|injuria racial|crime)\b/',
    ],

    'transito' => [
        'name' => 'Trânsito',
        'description' => 'Acidentes, rodovias e transporte',
        'pattern' => '/\b(transito|acidente|colisao|batida|capot\w*|tomba|tombou|rodovias?|raposo tavares|sp-?\d{3}|atropel\w*|onibus|transporte publico|motorista)\b/',
    ],

    'clima' => [
        'name' => 'Clima',
        'description' => 'Chuva, previsão do tempo e Defesa Civil',
        'pattern' => '/(?<!guarda-)\b(chuvas?|temporal|tempestade|alagament\w*|enchente|defesa civil|frente fria|onda de calor|inmet|previsao do tempo|granizo|vendaval|desabrigad\w*)\b/',
    ],

    'esportes' => [
        'name' => 'Esportes',
        'description' => 'Competições e atletas da cidade',
        'pattern' => '/\b(futsal|futebol|campeonato|torneio|natacao|nadadores|atletismo|jomi|joguinhos|jogos regionais|atletas?|tenis de mesa|corrida de rua|maratona|volei|basquete|esportes?|esportiv\w*)\b/',
    ],

    'saude' => [
        'name' => 'Saúde',
        'description' => 'Saúde pública, vacinação e hospitais',
        'pattern' => '/\b(saude|vacina\w*|dengue|hospital|ubs|posto de saude|medicos?|medicas?|sus|outubro rosa|novembro azul|doencas?)\b/',
    ],

    'educacao' => [
        'name' => 'Educação',
        'description' => 'Escolas, cursos e universidades',
        'pattern' => '/\b(escolas?|emei|emef|etec|alunos?|estudantes?|educacao|ensino|professor\w*|universidade|faculdade|matriculas?|cursos? de)\b/',
    ],

    'politica' => [
        'name' => 'Política',
        'description' => 'Câmara, Prefeitura e eleições',
        'pattern' => '/\b(vereador\w*|camara municipal|prefeito|prefeita|eleic\w*|eleitor\w*|urnas?|votos?|votacao|candidat\w*|deputad\w*|senador\w*|governador|presidente|2o turno|segundo turno)\b/',
    ],

    'cultura' => [
        'name' => 'Cultura',
        'description' => 'Eventos, shows, teatro e programação cultural',
        'pattern' => '/\b(show|shows|teatro|festival|exposicao|cinema|musica|musical|danca|espetaculo|literari\w*|livros?|biblioteca|cultura\w*|artesanato|desfile|historias)\b/',
    ],

];
