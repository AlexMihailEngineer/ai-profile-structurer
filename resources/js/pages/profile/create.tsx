import { Head, useForm } from '@inertiajs/react';
import React, { useState, useEffect, useRef } from 'react';
import { checkStatus, parse, store } from '@/actions/App/Http/Controllers/ProfileController';
import AppLayout from '@/layouts/app-layout';
// Assuming Wayfinder naming for your routes

interface Experience {
    company: string;
    title: string;
    start_date: string;
    end_date: string;
    description: string;
}

interface ProfileData {
    name: string;
    headline: string;
    about: string;
    location: string;
    skills: string[];
    experiences: Experience[];
}

export default function Create() {
    const [task_id, set_task_id] = useState<number | null>(null);
    const [parsing_status, set_parsing_status] = useState<'idle' | 'pending' | 'processing' | 'completed' | 'failed'>('idle');
    const [error_message, set_error_message] = useState<string | null>(null);
    const poll_timer = useRef<NodeJS.Timeout | null>(null);

    // 1. Initial Parse Request (Dispatches the Job)
    const [raw_text, set_raw_text] = useState('');
    const [is_submitting, set_is_submitting] = useState(false);

    // 2. Final Review Form (PostgreSQL Submission)
    const { data, setData, post, processing: is_saving, errors, reset } = useForm<ProfileData & { raw_text: string }>({
        name: '',
        headline: '',
        about: '',
        location: '',
        skills: [],
        experiences: [],
        raw_text: '',
    });

    // Function to check the status of the background job
    const check_status = async (id: number) => {
        try {
            // Using standard fetch or axios for polling to avoid Inertia overhead
            const response = await fetch(checkStatus.url(id));
            const result = await response.json();

            set_parsing_status(result.status);

            if (result.status === 'completed') {
                stop_polling();
                // Hydrate the review form with AI data
                setData({
                    ...result.data,
                    raw_text: raw_text
                });
            } else if (result.status === 'failed') {
                stop_polling();
                set_error_message(result.error || 'ai parsing failed.');
            }
        } catch (err) {
            console.error('polling error:', err);
        }
    };

    const stop_polling = () => {
        if (poll_timer.current) {
            clearInterval(poll_timer.current);
            poll_timer.current = null;
        }
    };

    const reset_form = () => {
        stop_polling();
        set_task_id(null);
        set_parsing_status('idle');
        set_error_message(null);
        set_raw_text('');
        reset();
    };

    const handle_parse = async (e: React.FormEvent) => {
        e.preventDefault();
        set_error_message(null);
        set_parsing_status('pending');

        const csrf_token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        try {
            set_is_submitting(true);

            const response = await fetch(parse.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    ...(csrf_token ? { 'X-CSRF-TOKEN': csrf_token } : {}),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ raw_text }),
            });

            if (!response.ok) {
                set_parsing_status('idle');
                set_error_message('failed to start parsing task.');

                return;
            }

            const result = await response.json();
            const id = result.task_id;

            if (typeof id !== 'number') {
                set_parsing_status('idle');
                set_error_message('failed to start parsing task.');

                return;
            }

            set_task_id(id);
            poll_timer.current = setInterval(() => check_status(id), 3000);
        } catch {
            set_parsing_status('idle');
            set_error_message('failed to start parsing task.');
        } finally {
            set_is_submitting(false);
        }
    };

    // Cleanup interval on unmount
    useEffect(() => () => stop_polling(), []);

    const update_exp = (idx: number, field: keyof Experience, val: string) => {
        const new_exps = [...data.experiences];
        new_exps[idx][field] = val;
        setData('experiences', new_exps);
    };

    return (
        <AppLayout>
            <Head title="create profile" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-white p-8 shadow-sm sm:rounded-lg">
                        
                        {/* HEADER */}
                        <div className="mb-8 border-b pb-4">
                            <h2 className="text-2xl font-bold lowercase text-gray-800">ai profile structurer</h2>
                            <p className="text-sm text-gray-500 lowercase">convert raw linkedin text into structured career data</p>
                        </div>

                        {/* STEP 1: INPUT & PROGRESS */}
                        {parsing_status !== 'completed' && (
                            <div className="space-y-6">
                                <form onSubmit={handle_parse} className="space-y-4">
                                    <textarea
                                        className="h-64 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                        placeholder="paste full profile content here..."
                                        value={raw_text}
                                        onChange={(e) => set_raw_text(e.target.value)}
                                        disabled={parsing_status !== 'idle' && parsing_status !== 'failed'}
                                    />
                                    
                                    {parsing_status === 'idle' || parsing_status === 'failed' ? (
                                        <div className="flex justify-end">
                                            <button type="submit" className="rounded-md bg-indigo-600 px-8 py-3 font-bold text-white hover:bg-indigo-700 lowercase">
                                                start ai analysis
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {/* PROGRESS BAR */}
                                            <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                                <div 
                                                    className={`h-full bg-indigo-600 transition-all duration-1000 ${parsing_status === 'processing' ? 'w-2/3' : 'w-1/3'}`}
                                                ></div>
                                            </div>
                                            <p className="animate-pulse text-center text-sm font-medium text-indigo-600 lowercase">
                                                {parsing_status}... kimi 2.5 is working on your data
                                            </p>
                                        </div>
                                    )}
                                </form>
                                {error_message && <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">{error_message}</div>}
                            </div>
                        )}

                        {/* STEP 2: THE REVIEW FORM */}
                        {parsing_status === 'completed' && (
                            <form onSubmit={(e) => {
 e.preventDefault();
 post(store.url(), {
     onSuccess: () => reset_form(),
     preserveState: false,
 });
}} className="space-y-6">
                                <div className="flex items-center justify-between border-b pb-2">
                                    <h3 className="text-lg font-bold text-gray-700 lowercase">review & correct</h3>
                                    <button type="button" onClick={() => reset_form()} className="text-xs text-gray-400 hover:text-red-500 lowercase">[restart]</button>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-1">
                                        <label className="text-xs font-bold text-gray-400 lowercase">name</label>
                                        <input className="w-full rounded-md border-gray-300" type="text" value={data.name} onChange={e => setData('name', e.target.value)} />
                                    </div>
                                    <div className="space-y-1">
                                        <label className="text-xs font-bold text-gray-400 lowercase">location</label>
                                        <input className="w-full rounded-md border-gray-300" type="text" value={data.location} onChange={e => setData('location', e.target.value)} />
                                    </div>
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-bold text-gray-400 lowercase">headline</label>
                                    <input className="w-full rounded-md border-gray-300" type="text" value={data.headline} onChange={e => setData('headline', e.target.value)} />
                                </div>

                                <div className="space-y-4">
                                    <h4 className="text-sm font-bold text-indigo-600 lowercase">work history</h4>
                                    {data.experiences.map((exp, i) => (
                                        <div key={i} className="space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4">
                                            <div className="grid grid-cols-2 gap-2">
                                                <input className="rounded border-gray-300 text-sm" placeholder="company" value={exp.company} onChange={e => update_exp(i, 'company', e.target.value)} />
                                                <input className="rounded border-gray-300 text-sm" placeholder="title" value={exp.title} onChange={e => update_exp(i, 'title', e.target.value)} />
                                            </div>
                                            <textarea className="h-20 w-full rounded border-gray-300 text-xs" value={exp.description} onChange={e => update_exp(i, 'description', e.target.value)} />
                                        </div>
                                    ))}
                                </div>

                                <button type="submit" disabled={is_saving} className="w-full rounded-md bg-green-600 py-4 font-bold text-white hover:bg-green-700 disabled:opacity-50 lowercase">
                                    {is_saving ? 'saving to postgresql...' : 'finalize and save profile'}
                                </button>
                            </form>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}