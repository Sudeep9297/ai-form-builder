import { Head, Link } from '@inertiajs/react';
import { FileText, LogIn, Plus, Sparkles } from 'lucide-react';

export default function Welcome({ auth }) {
    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="AI Form Builder" />
            <main className="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8">
                <nav className="flex items-center justify-between">
                    <div className="flex items-center gap-2 font-semibold text-gray-950">
                        <FileText className="h-6 w-6" />
                        AI Form Builder
                    </div>
                    <div className="flex gap-2">
                        {auth.user ? (
                            <Link href={route('forms.index')} className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Open forms</Link>
                        ) : (
                            <>
                                <Link href={route('login')} className="inline-flex items-center gap-2 rounded-md border bg-white px-4 py-2 text-sm font-semibold text-gray-900"><LogIn className="h-4 w-4" /> Log in</Link>
                                <Link href={route('register')} className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Register</Link>
                            </>
                        )}
                    </div>
                </nav>

                <section className="grid flex-1 items-center gap-10 py-12 lg:grid-cols-[1fr_420px]">
                    <div>
                        <p className="inline-flex items-center gap-2 rounded-md bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-800"><Sparkles className="h-4 w-4" /> JSON-schema forms with AI and imports</p>
                        <h1 className="mt-5 max-w-3xl text-4xl font-semibold tracking-normal text-gray-950 sm:text-5xl">Build, generate, import and publish production-ready forms.</h1>
                        <p className="mt-5 max-w-2xl text-lg text-gray-600">Use the demo account to try the full builder: drag fields, edit raw JSON, queue AI generation, import DOCX/XLSX, publish a public URL and export submissions.</p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href={auth.user ? route('forms.create') : route('login')} className="inline-flex items-center gap-2 rounded-md bg-gray-900 px-5 py-3 text-sm font-semibold text-white"><Plus className="h-4 w-4" /> Start building</Link>
                            <span className="rounded-md border bg-white px-4 py-3 text-sm text-gray-700">Demo: demo@example.com / password</span>
                        </div>
                    </div>
                    <div className="rounded-md border bg-white p-5 shadow-sm">
                        <div className="space-y-3">
                            {['Candidate Profile', 'Education History', 'Skills', 'Resume Upload'].map((label, index) => (
                                <div key={label} className="rounded-md border p-4">
                                    <p className="text-xs font-medium uppercase text-gray-500">Step {index + 1}</p>
                                    <p className="mt-1 font-semibold text-gray-950">{label}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </main>
        </div>
    );
}
