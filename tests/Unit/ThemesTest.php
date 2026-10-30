<?php

use App\Data\Item;
use App\Site\Themes;

function titled(string $title, string $summary = ''): Item
{
    return new Item('jornal', 'https://example.com/'.md5($title), $title, $summary);
}

it('classifica títulos reais no tema principal certo', function (string $title, string $theme) {
    expect(Themes::primary(titled($title)))->toBe($theme);
})->with([
    ['Nota de falecimento: Geraldo Doniseti Maschietto', 'obituarios'],
    ['Itapetininga se despede de “Edinho”, empresário e sócio da Funerária Camargo', 'obituarios'],
    ['PAT de Itapetininga tem 280 vagas de emprego nesta quinta-feira', 'empregos'],
    ['Prefeitura de Sarapuí abre concurso para Procurador Jurídico', 'empregos'],
    ['Após exposição em listas discriminatórias, comerciantes de Itapetininga procuram a Polícia Civil', 'policia'],
    ['Colisão entre caminhão e utilitário interdita trecho de rodovia em Itapetininga', 'transito'],
    ['Caminhão carregado com feijão tomba e deixa carga espalhada pela pista em Itapetininga', 'transito'],
    ['Alça de retorno na Raposo Tavares é liberada após alagamento em Itapetininga', 'transito'],
    ['Interdições, alagamentos e aumento do nível de rios: cidades da região de Itapetininga ainda vivem transtornos da chuva', 'clima'],
    ['Nadadores de Itapetininga conquistam 22 pódios no Torneio Regional em Bauru', 'esportes'],
    ['Corrida de rua ganha cada vez mais espaço no Brasil', 'esportes'],
    ['El Niño pode agravar casos de dengue em 2027, aponta especialista', 'saude'],
    ['Após reclamações de problemas estruturais, Prefeitura transfere escola do Vale San Fernando para FKB', 'educacao'],
    ['Flávio Bolsonaro recebe 66,75% dos votos em Itapetininga no 2º turno', 'politica'],
    ['20 urnas são substituídas na região durante o segundo turno', 'politica'],
    ['“Teatro Literário” apresenta peça', 'cultura'],
    ['5ª Edição da Tarde de Histórias é realizada em Itapetininga', 'cultura'],
]);

it('dá prioridade ao tema mais específico quando há vários', function () {
    // Fala de polícia e de futsal: polícia vem antes de esportes.
    $item = titled('Menino de 12 anos relata injúria racial durante partida de futsal em Itapetininga');

    expect(Themes::for($item))->toBe(['policia', 'esportes'])
        ->and(Themes::primary($item))->toBe('policia');
});

it('dá preferência ao título sobre o resumo', function () {
    $urnas = titled(
        'Eleições 2026: urnas eletrônicas são levadas aos locais de votação na região de Itapetininga',
        'Os equipamentos, que precisaram ser transportados sob escolta policial, serão utilizados em Itapetininga.',
    );

    expect(Themes::primary($urnas))->toBe('politica')
        ->and(Themes::for($urnas))->toBe(['politica', 'policia']);
});

it('não confunde guarda-chuva com chuva', function () {
    expect(Themes::primary(titled(
        '5ª Edição da Tarde de Histórias é realizada em Itapetininga',
        'Você já imaginou quantas aventuras podem se esconder debaixo de um guarda-chuva azul?',
    )))->toBe('cultura');
});

it('usa também o resumo para decidir', function () {
    expect(Themes::primary(titled('Programa cadastra famílias no bairro Rio Acima', 'Ação vai melhorar o saneamento e a saúde da população.')))
        ->toBe('saude');
});

it('não força tema em notícias que não se encaixam', function (string $title) {
    expect(Themes::primary(titled($title)))->toBeNull();
})->with([
    'CPFL Santa Cruz lança campanha de bônus para clientes',
    'Governo prorroga subsídio ao diesel por mais 30 dias',
]);

it('não confunde palavras genéricas com o tema', function () {
    // "apresentaram problemas" não é cultura; "feira livre" também não.
    expect(Themes::for(titled('Quatro equipamentos apresentaram problemas na feira livre')))->toBe([]);
});

it('ignora acentos e maiúsculas', function () {
    expect(Themes::primary(titled('POLÍCIA CIVIL PRENDE SUSPEITO')))->toBe('policia');
});
