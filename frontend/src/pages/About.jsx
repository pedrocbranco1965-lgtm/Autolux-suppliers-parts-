function About() {
  return (
    <section className="section container narrow">
      <div className="section-heading">
        <p className="eyebrow">Sobre nós</p>
        <h1>AutoLux</h1>
        <p>Um stand automóvel online focado em confiança, transparência e acompanhamento personalizado.</p>
      </div>

      <div className="content-card">
        <h2>A nossa missão</h2>
        <p>
          A AutoLux apresenta uma seleção cuidada de viaturas novas e usadas, com informação simples de consultar e ferramentas para guardar favoritos, comparar opções e solicitar contacto.
        </p>
        <p>
          Este projeto foi desenvolvido em React para demonstrar rotas, componentes reutilizáveis, estado local, Context API, carregamento de dados por fetch e validação de formulário.
        </p>
      </div>
    </section>
  );
}

export default About;
