import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useVehicles } from '../hooks/useVehicles.js';

const initialForm = {
  nome: '',
  email: '',
  telefone: '',
  mensagem: '',
  veiculos: []
};

function Contact() {
  const { vehicles, loading } = useVehicles();
  const [searchParams] = useSearchParams();
  const [form, setForm] = useState(initialForm);
  const [errors, setErrors] = useState({});
  const [success, setSuccess] = useState(false);

  useEffect(() => {
    const vehicleId = searchParams.get('veiculo');
    if (vehicleId) {
      setForm((currentForm) => ({ ...currentForm, veiculos: [vehicleId] }));
    }
  }, [searchParams]);

  function handleChange(event) {
    const { name, value } = event.target;
    setForm((currentForm) => ({ ...currentForm, [name]: value }));
  }

  function handleVehicleSelection(event) {
    const selectedValues = Array.from(event.target.selectedOptions).map((option) => option.value);
    setForm((currentForm) => ({ ...currentForm, veiculos: selectedValues }));
  }

  function validateForm() {
    const newErrors = {};
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!form.nome.trim()) newErrors.nome = 'O nome é obrigatório.';
    if (!form.email.trim()) newErrors.email = 'O email é obrigatório.';
    if (form.email && !emailRegex.test(form.email)) newErrors.email = 'Introduza um email válido.';
    if (!form.telefone.trim()) newErrors.telefone = 'O telefone é obrigatório.';
    if (!form.mensagem.trim()) newErrors.mensagem = 'A mensagem é obrigatória.';
    if (form.veiculos.length === 0) newErrors.veiculos = 'Selecione pelo menos uma viatura.';

    return newErrors;
  }

  function handleSubmit(event) {
    event.preventDefault();
    const validationErrors = validateForm();
    setErrors(validationErrors);

    if (Object.keys(validationErrors).length === 0) {
      setSuccess(true);
      setForm(initialForm);
    } else {
      setSuccess(false);
    }
  }

  return (
    <section className="section container narrow">
      <div className="section-heading">
        <p className="eyebrow">Contacto / Proposta</p>
        <h1>Pedido de contacto</h1>
        <p>Preencha o formulário e indique uma ou mais viaturas de interesse.</p>
      </div>

      {success && <p className="success-message">Simulacao concluida. Nenhum pedido foi enviado ou guardado no servidor.</p>}

      <form className="contact-form" onSubmit={handleSubmit} noValidate>
        <label>
          Nome *
          <input type="text" name="nome" value={form.nome} onChange={handleChange} />
          {errors.nome && <span className="field-error">{errors.nome}</span>}
        </label>

        <label>
          Email *
          <input type="email" name="email" value={form.email} onChange={handleChange} />
          {errors.email && <span className="field-error">{errors.email}</span>}
        </label>

        <label>
          Telefone *
          <input type="tel" name="telefone" value={form.telefone} onChange={handleChange} />
          {errors.telefone && <span className="field-error">{errors.telefone}</span>}
        </label>

        <label>
          Viatura(s) de interesse *
          <select multiple name="veiculos" value={form.veiculos} onChange={handleVehicleSelection}>
            {loading && <option>A carregar viaturas...</option>}
            {vehicles.map((vehicle) => (
              <option key={vehicle.id} value={vehicle.id}>
                {vehicle.marca} {vehicle.modelo} - {vehicle.ano}
              </option>
            ))}
          </select>
          <small>Para selecionar mais do que uma viatura, use Ctrl/Cmd + clique.</small>
          {errors.veiculos && <span className="field-error">{errors.veiculos}</span>}
        </label>

        <label>
          Mensagem *
          <textarea name="mensagem" rows="5" value={form.mensagem} onChange={handleChange} />
          {errors.mensagem && <span className="field-error">{errors.mensagem}</span>}
        </label>

        <button className="button" type="submit">Enviar pedido</button>
      </form>
    </section>
  );
}

export default Contact;
