@php
$configData = Helper::appClasses();
$pageConfigs = ['myLayout' => 'front'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Termos de Uso')

{{-- Base: front-pages/help-center-article --}}
@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="card"><div class="card-body p-md-8">
      <h4 class="mb-2">Termos de Uso</h4>
      <div class="alert alert-warning">Modelo inicial — o texto definitivo deve ser redigido e validado por assessoria jurídica antes do lançamento.</div>
      <h6>1. Papel da plataforma</h6>
      <p>A {{ config('variables.templateName') }} é um software (SaaS) que permite a organizadores divulgar rifas, receber pagamentos por meio de instituição de pagamento parceira e registrar sorteios. A plataforma não é a promotora das rifas.</p>
      <h6>2. Responsabilidade do organizador</h6>
      <p>O organizador declara possuir as autorizações exigidas pela legislação (Lei 5.768/1971, Decreto 70.951/1972 e normas da Secretaria de Prêmios e Apostas), responde pela existência e entrega do prêmio e pela veracidade das informações publicadas.</p>
      <h6>3. Publicação e moderação</h6>
      <p>Toda rifa passa por análise antes da publicação. A plataforma pode suspender rifas e contas em caso de irregularidade, denúncia ou suspeita de fraude, com reembolso dos participantes.</p>
      <h6>4. Participantes</h6>
      <p>É vedada a participação de menores de 18 anos. A compra é confirmada somente após a compensação do Pix; reservas não pagas expiram automaticamente.</p>
      <h6>5. Sorteio</h6>
      <p>As regras do sorteio são definidas antes do início das vendas e não podem ser alteradas após a publicação. A prova do sorteio fica disponível publicamente.</p>
    </div></div>
  </div>
</section>
@endsection
