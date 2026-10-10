@php
$configData = Helper::appClasses();
$pageConfigs = ['myLayout' => 'front'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Política de Privacidade')

{{-- Base: front-pages/help-center-article --}}
@section('content')
<section class="section-py first-section-pt">
  <div class="container">
    <div class="card"><div class="card-body p-md-8">
      <h4 class="mb-2">Política de Privacidade</h4>
      <div class="alert alert-warning">Modelo inicial — o texto definitivo deve ser redigido e validado por assessoria jurídica antes do lançamento.</div>
      <h6>1. Dados tratados</h6>
      <p>Dos participantes: nome, CPF, telefone, e-mail (opcional), data de nascimento e dados do pedido. Dos organizadores: dados cadastrais, documentos de verificação e dados de recebimento.</p>
      <h6>2. Finalidades e base legal (LGPD, Lei 13.709/2018)</h6>
      <p>Execução de contrato (registro da participação, pagamento, sorteio e entrega do prêmio), cumprimento de obrigação legal (prestação de contas e prevenção a fraudes) e legítimo interesse para segurança da plataforma.</p>
      <h6>3. Compartilhamento</h6>
      <p>Com o organizador da rifa (dados necessários à entrega do prêmio), com a instituição de pagamento e provedores de e-mail/hospedagem, sob contrato. O CPF é exibido sempre mascarado em telas e relatórios.</p>
      <h6>4. Retenção e direitos</h6>
      <p>Os dados são mantidos pelo prazo necessário à prestação de contas da rifa e às obrigações legais. O titular pode solicitar acesso, correção ou exclusão pelo canal do encarregado (DPO).</p>
    </div></div>
  </div>
</section>
@endsection
