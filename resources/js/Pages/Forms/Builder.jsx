import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { DragDropContext, Draggable, Droppable } from '@hello-pangea/dnd';
import { Copy, Download, Eye, FileUp, GripVertical, Plus, RotateCcw, Save, Sparkles, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

const fieldTypes = ['text', 'textarea', 'number', 'email', 'phone', 'date', 'dropdown', 'radio', 'checkbox', 'file', 'section', 'rating', 'url'];

const uuid = () => crypto.randomUUID();
const titleize = (value) => value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
const keyFrom = (label) => label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || 'field';

function newField(type) {
    const label = titleize(type);
    return {
        id: uuid(),
        type,
        label,
        key: keyFrom(label),
        placeholder: type === 'section' ? '' : label,
        helpText: '',
        default: type === 'checkbox' ? [] : null,
        required: false,
        options: ['dropdown', 'radio', 'checkbox'].includes(type) ? [{ label: 'Option 1', value: 'option_1' }] : [],
        validation: type === 'file' ? { fileTypes: ['pdf'], maxFileSizeKb: 5120 } : {},
    };
}

export default function Builder({ auth, form, schema, versions = [], submissions = { data: [] }, publicUrl }) {
    const flash = usePage().props.flash || {};
    const [draft, setDraft] = useState(schema);
    const [selected, setSelected] = useState(null);
    const [raw, setRaw] = useState(JSON.stringify(schema, null, 2));
    const [rawError, setRawError] = useState('');
    const [aiPrompt, setAiPrompt] = useState('');
    const [importPreview, setImportPreview] = useState(null);
    const submit = useForm({});
    const ai = useForm({ prompt: '', mode: form ? 'edit' : 'create', form_id: form?.id });
    const importer = useForm({ source: null });

    const fields = useMemo(() => draft.steps?.flatMap((step) => step.fields || []) || [], [draft]);
    const analytics = useMemo(() => {
        const total = submissions.total ?? submissions.data?.length ?? 0;
        return { total, fields: fields.filter((f) => f.type !== 'section').length, completion: total ? '100%' : '0%' };
    }, [fields, submissions]);

    const sync = (next) => {
        setDraft(next);
        setRaw(JSON.stringify(next, null, 2));
    };

    const addField = (type) => {
        const next = structuredClone(draft);
        next.steps[0].fields.push(newField(type));
        sync(next);
    };

    const updateField = (id, patch) => {
        const next = structuredClone(draft);
        next.steps.forEach((step) => step.fields = step.fields.map((field) => field.id === id ? { ...field, ...patch } : field));
        sync(next);
    };

    const removeField = (id) => {
        const next = structuredClone(draft);
        next.steps.forEach((step) => step.fields = step.fields.filter((field) => field.id !== id));
        sync(next);
        setSelected(null);
    };

    const duplicateField = (field) => {
        const next = structuredClone(draft);
        const step = next.steps.find((item) => item.fields.some((f) => f.id === field.id));
        const index = step.fields.findIndex((f) => f.id === field.id);
        step.fields.splice(index + 1, 0, { ...field, id: uuid(), key: `${field.key}_copy`, label: `${field.label} copy` });
        sync(next);
    };

    const onDragEnd = ({ source, destination }) => {
        if (!destination) return;
        const next = structuredClone(draft);
        const fields = next.steps[0].fields;
        const [moved] = fields.splice(source.index, 1);
        fields.splice(destination.index, 0, moved);
        sync(next);
    };

    const applyRaw = () => {
        try {
            const parsed = JSON.parse(raw);
            setRawError('');
            sync(parsed);
        } catch (error) {
            setRawError(error.message);
        }
    };

    const save = () => {
        const payload = { title: draft.title, description: draft.description, schema: draft, is_published: !!form?.is_published };
        form ? router.put(route('forms.update', form.id), payload) : router.post(route('forms.store'), payload);
    };

    const publish = () => {
        const payload = { title: draft.title, description: draft.description, schema: draft, is_published: true };
        form ? router.put(route('forms.update', form.id), payload) : router.post(route('forms.store'), payload);
    };

    const selectedField = fields.find((field) => field.id === selected);

    return (
        <AuthenticatedLayout auth={auth} header={<h2 className="text-xl font-semibold leading-tight text-gray-900">{form ? form.title : 'New form'}</h2>}>
            <Head title={form ? form.title : 'New form'} />
            <div className="mx-auto max-w-[1500px] px-4 py-6 sm:px-6 lg:px-8">
                {flash.success && <div className="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{flash.success}</div>}
                <div className="grid gap-5 xl:grid-cols-[240px_minmax(0,1fr)_360px]">
                    <aside className="space-y-4">
                        <section className="rounded-md border bg-white p-4">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Fields</h3>
                            <div className="mt-3 grid grid-cols-2 gap-2">
                                {fieldTypes.map((type) => (
                                    <button key={type} type="button" onClick={() => addField(type)} className="rounded-md border px-2 py-2 text-left text-sm hover:bg-gray-50">
                                        <Plus className="mb-1 h-4 w-4" /> {titleize(type)}
                                    </button>
                                ))}
                            </div>
                        </section>
                        <section className="rounded-md border bg-white p-4">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">AI</h3>
                            <textarea value={ai.data.prompt} onChange={(e) => ai.setData('prompt', e.target.value)} className="mt-3 h-24 w-full rounded-md border-gray-300 text-sm" placeholder="Add an emergency contact section" />
                            <button onClick={() => ai.post(route('ai-generations.store'))} className="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white"><Sparkles className="h-4 w-4" /> Generate</button>
                            {flash.aiGenerationId && <p className="mt-2 text-xs text-gray-600">AI job queued: #{flash.aiGenerationId}. Open `/ai-generations/{flash.aiGenerationId}` for status.</p>}
                        </section>
                        <section className="rounded-md border bg-white p-4">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Import</h3>
                            <input type="file" accept=".docx,.xlsx" onChange={(e) => importer.setData('source', e.target.files[0])} className="mt-3 text-sm" />
                            <button onClick={() => importer.post(route('imports.store'))} className="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm"><FileUp className="h-4 w-4" /> Upload</button>
                            {flash.importBatchId && <p className="mt-2 text-xs text-gray-600">Import queued: #{flash.importBatchId}. Review JSON status before committing.</p>}
                        </section>
                    </aside>

                    <main className="space-y-4">
                        <section className="rounded-md border bg-white p-4">
                            <div className="grid gap-3 md:grid-cols-2">
                                <input value={draft.title} onChange={(e) => sync({ ...draft, title: e.target.value })} className="rounded-md border-gray-300 text-2xl font-semibold" />
                                <div className="flex justify-end gap-2">
                                    {publicUrl && <a href={publicUrl} target="_blank" className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm"><Eye className="h-4 w-4" /> Public</a>}
                                    {form && <a href={route('forms.submissions.csv', form.id)} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm"><Download className="h-4 w-4" /> CSV</a>}
                                    <button onClick={save} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm"><Save className="h-4 w-4" /> Save</button>
                                    <button onClick={publish} className="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white">Publish</button>
                                </div>
                            </div>
                            <textarea value={draft.description || ''} onChange={(e) => sync({ ...draft, description: e.target.value })} className="mt-3 w-full rounded-md border-gray-300 text-sm" placeholder="Description" />
                        </section>

                        <section className="rounded-md border bg-white p-4">
                            <DragDropContext onDragEnd={onDragEnd}>
                                <Droppable droppableId="fields">
                                    {(provided) => (
                                        <div ref={provided.innerRef} {...provided.droppableProps} className="space-y-3">
                                            {draft.steps[0].fields.map((field, index) => (
                                                <Draggable key={field.id} draggableId={field.id} index={index}>
                                                    {(drag) => (
                                                        <div ref={drag.innerRef} {...drag.draggableProps} className={`rounded-md border p-4 ${selected === field.id ? 'border-gray-900' : 'border-gray-200'}`} onClick={() => setSelected(field.id)}>
                                                            <div className="flex items-start gap-3">
                                                                <button {...drag.dragHandleProps} className="mt-2 text-gray-400"><GripVertical className="h-4 w-4" /></button>
                                                                <div className="min-w-0 flex-1">
                                                                    <input value={field.label} onChange={(e) => updateField(field.id, { label: e.target.value, key: keyFrom(e.target.value) })} className="w-full border-0 p-0 text-base font-semibold focus:ring-0" />
                                                                    <p className="mt-1 text-xs uppercase text-gray-500">{field.type} · {field.key}</p>
                                                                </div>
                                                                <button onClick={(e) => { e.stopPropagation(); duplicateField(field); }} className="rounded p-2 hover:bg-gray-100"><Copy className="h-4 w-4" /></button>
                                                                <button onClick={(e) => { e.stopPropagation(); removeField(field.id); }} className="rounded p-2 text-red-700 hover:bg-red-50"><Trash2 className="h-4 w-4" /></button>
                                                            </div>
                                                        </div>
                                                    )}
                                                </Draggable>
                                            ))}
                                            {provided.placeholder}
                                        </div>
                                    )}
                                </Droppable>
                            </DragDropContext>
                        </section>

                        <section className="rounded-md border bg-white p-4">
                            <div className="mb-2 flex items-center justify-between"><h3 className="font-semibold">Raw JSON schema</h3><button onClick={applyRaw} className="rounded-md border px-3 py-1 text-sm">Apply JSON</button></div>
                            <textarea value={raw} onChange={(e) => setRaw(e.target.value)} className="h-80 w-full rounded-md border-gray-300 font-mono text-xs" />
                            {rawError && <p className="mt-2 text-sm text-red-700">{rawError}</p>}
                        </section>
                    </main>

                    <aside className="space-y-4">
                        <section className="rounded-md border bg-white p-4">
                            <h3 className="font-semibold">Field settings</h3>
                            {selectedField ? (
                                <div className="mt-3 space-y-3">
                                    <label className="block text-sm">Type<select value={selectedField.type} onChange={(e) => updateField(selectedField.id, { type: e.target.value })} className="mt-1 w-full rounded-md border-gray-300">{fieldTypes.map((type) => <option key={type}>{type}</option>)}</select></label>
                                    <label className="block text-sm">Key<input value={selectedField.key} onChange={(e) => updateField(selectedField.id, { key: e.target.value })} className="mt-1 w-full rounded-md border-gray-300" /></label>
                                    <label className="block text-sm">Placeholder<input value={selectedField.placeholder || ''} onChange={(e) => updateField(selectedField.id, { placeholder: e.target.value })} className="mt-1 w-full rounded-md border-gray-300" /></label>
                                    <label className="block text-sm">Help text<input value={selectedField.helpText || ''} onChange={(e) => updateField(selectedField.id, { helpText: e.target.value })} className="mt-1 w-full rounded-md border-gray-300" /></label>
                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={selectedField.required} onChange={(e) => updateField(selectedField.id, { required: e.target.checked })} /> Required</label>
                                    <label className="block text-sm">Options<textarea value={(selectedField.options || []).map((o) => o.label).join('\n')} onChange={(e) => updateField(selectedField.id, { options: e.target.value.split('\n').filter(Boolean).map((label) => ({ label, value: keyFrom(label) })) })} className="mt-1 h-24 w-full rounded-md border-gray-300" /></label>
                                    <label className="block text-sm">Validation JSON<textarea value={JSON.stringify(selectedField.validation || {}, null, 2)} onChange={(e) => { try { updateField(selectedField.id, { validation: JSON.parse(e.target.value || '{}') }); } catch (_) {} }} className="mt-1 h-28 w-full rounded-md border-gray-300 font-mono text-xs" /></label>
                                </div>
                            ) : <p className="mt-3 text-sm text-gray-600">Select a field to edit label, key, options and validation.</p>}
                        </section>

                        <section className="rounded-md border bg-white p-4">
                            <h3 className="font-semibold">Analytics</h3>
                            <div className="mt-3 grid grid-cols-3 gap-2 text-sm">
                                <div><p className="text-gray-500">Fields</p><p className="text-xl font-semibold">{analytics.fields}</p></div>
                                <div><p className="text-gray-500">Subs</p><p className="text-xl font-semibold">{analytics.total}</p></div>
                                <div><p className="text-gray-500">Complete</p><p className="text-xl font-semibold">{analytics.completion}</p></div>
                            </div>
                        </section>

                        {form && <section className="rounded-md border bg-white p-4">
                            <h3 className="font-semibold">Versions</h3>
                            <div className="mt-3 space-y-2">
                                {versions.map((version) => (
                                    <button key={version.id} onClick={() => router.post(route('forms.rollback', [form.id, version.id]))} className="flex w-full items-center justify-between rounded-md border px-3 py-2 text-sm">
                                        <span>v{version.version} {version.change_summary}</span><RotateCcw className="h-4 w-4" />
                                    </button>
                                ))}
                            </div>
                        </section>}
                    </aside>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
