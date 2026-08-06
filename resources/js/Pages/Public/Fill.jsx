import { Head, useForm } from '@inertiajs/react';

function Field({ field, value, onChange, error }) {
    const common = 'mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900';
    if (field.type === 'section') return <h2 className="border-b pb-2 text-lg font-semibold">{field.label}</h2>;
    if (field.type === 'textarea') return <textarea className={common} value={value || ''} onChange={(e) => onChange(e.target.value)} placeholder={field.placeholder} />;
    if (['dropdown', 'radio', 'checkbox'].includes(field.type)) {
        if (field.type === 'dropdown') return <select className={common} value={value || ''} onChange={(e) => onChange(e.target.value)}><option value="">Choose...</option>{field.options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}</select>;
        return <div className="mt-2 space-y-2">{field.options.map((o) => <label key={o.value} className="flex items-center gap-2 text-sm"><input type={field.type === 'radio' ? 'radio' : 'checkbox'} checked={field.type === 'radio' ? value === o.value : (value || []).includes(o.value)} onChange={(e) => field.type === 'radio' ? onChange(o.value) : onChange(e.target.checked ? [...(value || []), o.value] : (value || []).filter((v) => v !== o.value))} /> {o.label}</label>)}</div>;
    }
    if (field.type === 'rating') return <input type="number" min="1" max="5" className={common} value={value || ''} onChange={(e) => onChange(e.target.value)} />;
    if (field.type === 'file') return <input type="file" className={common} onChange={(e) => onChange(e.target.files[0] || null)} />;
    return <input type={field.type === 'phone' ? 'tel' : field.type} className={common} value={value || ''} onChange={(e) => onChange(e.target.value)} placeholder={field.placeholder} />;
}

export default function Fill({ form }) {
    const fields = form.schema.steps.flatMap((step) => step.fields);
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({ answers: {}, website: '' });

    return (
        <div className="min-h-screen bg-gray-50 px-4 py-10">
            <Head title={form.title} />
            <main className="mx-auto max-w-3xl rounded-md border bg-white p-6 shadow-sm">
                <h1 className="text-2xl font-semibold text-gray-950">{form.title}</h1>
                <p className="mt-2 text-gray-600">{form.description}</p>
                {recentlySuccessful && <div className="mt-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Thanks. Your response was submitted.</div>}
                <form onSubmit={(e) => { e.preventDefault(); post(route('public.forms.submit', form.token)); }} className="mt-6 space-y-5">
                    <input type="text" value={data.website} onChange={(e) => setData('website', e.target.value)} className="hidden" tabIndex="-1" autoComplete="off" />
                    {fields.map((field) => (
                        <label key={field.id} className="block">
                            {field.type !== 'section' && <span className="text-sm font-medium text-gray-900">{field.label}{field.required && ' *'}</span>}
                            <Field field={field} value={data.answers[field.key]} onChange={(value) => setData('answers', { ...data.answers, [field.key]: value })} />
                            {field.helpText && <span className="mt-1 block text-xs text-gray-500">{field.helpText}</span>}
                            {errors[`answers.${field.key}`] && <span className="mt-1 block text-sm text-red-700">{errors[`answers.${field.key}`]}</span>}
                        </label>
                    ))}
                    <button disabled={processing} className="rounded-md bg-gray-900 px-5 py-2 text-sm font-semibold text-white disabled:opacity-60">Submit</button>
                </form>
            </main>
        </div>
    );
}
