import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Download, ExternalLink, FileText, Pencil, Plus, Trash2 } from 'lucide-react';

export default function Index({ auth, forms }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-gray-900">Forms</h2>}
        >
            <Head title="Forms" />
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <p className="text-sm text-gray-600">Create, publish, import and analyze schema-driven forms.</p>
                    </div>
                    <Link href={route('forms.create')} className="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">
                        <Plus className="h-4 w-4" /> New form
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {forms.data.map((form) => (
                        <div key={form.id} className="rounded-md border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-950">{form.title}</h3>
                                    <p className="mt-1 line-clamp-2 text-sm text-gray-600">{form.description || 'No description'}</p>
                                </div>
                                <span className={`rounded px-2 py-1 text-xs font-medium ${form.is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}`}>
                                    {form.is_published ? 'Published' : 'Draft'}
                                </span>
                            </div>
                            <dl className="mt-5 grid grid-cols-3 gap-3 text-sm">
                                <div><dt className="text-gray-500">Version</dt><dd className="font-semibold">v{form.version}</dd></div>
                                <div><dt className="text-gray-500">Submissions</dt><dd className="font-semibold">{form.submissions_count ?? 0}</dd></div>
                                <div><dt className="text-gray-500">Updated</dt><dd className="font-semibold">{form.updated_at?.slice(0, 10)}</dd></div>
                            </dl>
                            <div className="mt-5 flex flex-wrap gap-2">
                                <Link href={route('forms.edit', form.id)} className="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm"><Pencil className="h-4 w-4" /> Edit</Link>
                                <a href={route('forms.submissions.csv', form.id)} className="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm"><Download className="h-4 w-4" /> CSV</a>
                                <button onClick={() => router.delete(route('forms.destroy', form.id))} className="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm text-red-700"><Trash2 className="h-4 w-4" /> Delete</button>
                            </div>
                        </div>
                    ))}
                </div>

                {forms.data.length === 0 && (
                    <div className="rounded-md border border-dashed bg-white p-10 text-center">
                        <FileText className="mx-auto h-10 w-10 text-gray-400" />
                        <p className="mt-3 font-medium">No forms yet</p>
                        <Link href={route('forms.create')} className="mt-4 inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"><Plus className="h-4 w-4" /> Start building</Link>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
