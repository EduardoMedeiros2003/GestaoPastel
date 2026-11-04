# Leo do Pastel – Vendas

CRM para a equipe comercial de uma pastelaria que vende para outras empresas: coffee breaks e
eventos corporativos, festas e casamentos, revenda de pastel congelado para bares e mercados, e
franquias.

A ideia é o sistema dizer ao vendedor **o que fazer hoje**, em vez de só guardar o que ele já fez.
Cada negócio recebe uma nota de 0 a 100 com a explicação de cada ponto, negócios sem contato são
marcados como parados, e a tela inicial monta a lista de tarefas do dia.

Feito só com PHP: sem JavaScript, sem framework, sem Composer e sem banco de dados.

## Como rodar

Precisa de PHP 8.1 ou mais novo.

```bash
git clone <url-do-repositorio>
cd vaga-crm-pulso
php -S 127.0.0.1:8090
```

Abra http://127.0.0.1:8090.

Na primeira vez o sistema cria dados de exemplo (clientes, negócios e contatos fictícios), com datas
calculadas a partir do dia atual. Para começar do zero, apague a pasta `data/`; ela é recriada no
próximo acesso.

Também roda em qualquer hospedagem PHP comum: basta enviar os arquivos e garantir que o PHP possa
escrever na pasta `data/`.

## Telas

- **Hoje**: quanto foi vendido no trimestre em relação à meta, quanto está em negociação, quantos
  negócios estão parados, a lista do que fazer hoje e os últimos contatos da equipe.
- **Funil**: negócios abertos por etapa (Contato, Briefing, Degustação, Orçamento, Fechando), com
  filtro para ver todos, só os meus ou só os parados.
- **Negócio**: dados do negócio, nota explicada, registro de contato (ligação, WhatsApp, reunião,
  e-mail, anotação), histórico, e os botões Ganhou, Perdeu (com motivo), Reabrir e Excluir.
- **Clientes**: lista com contato principal, valor em aberto e quanto cada cliente já comprou.
- **Relatórios**: vendas por pessoa, quantos negócios chegam em cada etapa, motivos de perda e taxa
  de ganho por origem (Indicação, Instagram, WhatsApp, Balcão, Feira, Google).
- **Novo negócio** e **Busca**.

Tem tema claro e escuro. O padrão segue o sistema operacional, e a escolha feita na barra lateral
fica salva num cookie.

## Como funciona

### Nota do negócio

Vai de 0 a 100 e é a soma de alguns itens:

| Item | Pontos |
|---|---|
| Etapa | até 28 (35% da chance da etapa) |
| Último contato | até 25 se recente; negativo se o negócio está parado |
| Contatos nas últimas 2 semanas | até 20 (reunião vale mais que e-mail) |
| Próximo passo | +10 se marcado, -5 se atrasado, -10 se não existe |
| Data de fechamento | +10 se nos próximos 30 dias, +5 se depois, -10 se já passou |
| Alguém do cliente defende a compra | +5 |

A página do negócio mostra cada item com os pontos que deu, para o vendedor poder discordar da nota.

### Negócio parado

Cada etapa tem um prazo sem contato: 3 dias em Contato, 5 em Briefing, 7 em Degustação, 5 em
Orçamento e 4 em Fechando. Passou do prazo, o negócio aparece como parado, **a não ser que tenha um
próximo passo marcado para frente**. Assim, quem já está com visita agendada não é marcado à toa.

### Tarefas do dia

Saem de regras simples:

- próximo passo marcado para hoje ou atrasado;
- negócio parado (retomar contato);
- negócio sem próximo passo;
- data de fechamento que já passou;
- negócio com nota alta em etapa avançada (hora de pedir o fechamento).

A lista é ordenada pelo valor esperado (valor do negócio × chance da etapa), com peso maior para o
que está atrasado. Marcar uma tarefa como feita registra um contato no negócio.

### Criar negócio escrevendo uma frase

A busca do topo também cria negócios. Por exemplo:

```
Colégio Aurora 800 pastéis 9,6k orçamento sexta via whatsapp #festa
```

vira: cliente *Colégio Aurora*, *800 pastéis*, valor *R$ 9.600*, etapa *Orçamento*, fechamento na
*próxima sexta*, origem *WhatsApp* e etiqueta *festa*. O sistema mostra o que entendeu antes de criar.
Se o cliente não existe, ele é cadastrado.

Quantidades (`800 pastéis`, `300 pessoas`) são lidas antes do valor, para não virarem R$ 800. Valores
aceitam `9,6k`, `12 mil`, `R$ 2.400` e `2.400,50`.

## Decisões técnicas

- **Só PHP.** Cada tela é montada no servidor e cada ação é um formulário. Depois de gravar, a
  página redireciona (POST → redirect → GET), então atualizar a página não grava de novo.
- **Dados em arquivos PHP.** Cada coleção fica em `data/<nome>.php`, um arquivo que só devolve um
  array. A gravação usa uma trava (`flock`) e troca atômica (grava num arquivo temporário e
  renomeia), para duas pessoas salvando ao mesmo tempo não corromperem o arquivo. Se alguém abrir
  esses arquivos pelo navegador, não aparece nada.
- **Cálculo a cada leitura.** Nota, parados e tarefas são calculados quando a página abre, então
  nunca ficam desatualizados.
- **Segurança básica.** Todo formulário leva um token CSRF da sessão. Os campos recebidos passam por
  uma validação com lista do que é permitido. Todo texto vindo do usuário é escapado antes de ir
  para a tela.
- **Trocar o armazenamento.** `src/Store.php` tem métodos simples (`all`, `find`, `insert`,
  `update`, `delete`). Para usar MySQL ou SQLite, basta reescrever essa classe.

## Estrutura

```
index.php            recebe os formulários e escolhe a tela
src/
  Crm.php            validação e gravação de negócios, contatos e tarefas
  Sales.php          nota, negócios parados, tarefas do dia e relatórios
  QuickParser.php    leitura da frase digitada na busca
  Store.php          gravação dos dados em data/*.php
  Seeder.php         dados de exemplo, etapas, prazos e motivos de perda
  helpers.php        formatação, ícones, CSRF, mensagens, logo
views/
  layout.php         estrutura comum (menu, busca, tema)
  hoje.php, funil.php, negocio.php, clientes.php, relatorios.php, novo.php, busca.php
  _estilo.php        estilo das telas
data/                criada pelo sistema (não versionar)
```

Etapas, prazos, meta do trimestre, motivos de perda e equipe ficam em `src/Seeder.php`
(`Seeder::settings()` e `Seeder::run()`).

## Limitações

- Não tem login: todo mundo entra como o Leo.
- Não registra contatos do WhatsApp automaticamente.
- Os pesos da nota são fixos. O próximo passo seria ajustá-los a partir dos negócios já ganhos e
  perdidos.
