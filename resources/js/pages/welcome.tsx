import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useAppearance } from '@/hooks/use-appearance';

interface PageProps extends SharedData {
    flash?: {
        error?: string;
        success?: string;
    };
}

export default function Welcome() {
    const { auth, flash = {} } = usePage<PageProps>().props;
    const { appearance, updateAppearance } = useAppearance();
    const { data, setData, post, processing, errors } = useForm({
        code: '',
    });

    const toggleTheme = () => {
        updateAppearance(appearance === 'dark' ? 'light' : 'dark');
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('voter.login'), {
            onError: (errors) => {
                console.error(errors);
            },
            preserveScroll: true,
        });
    };

    const handleCodeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData('code', e.target.value);
    };

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet" />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
                    <nav className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex items-center space-x-2">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg">
                                    <img src="images/spup-logo.png" alt="SPUP Logo" />
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-sm text-[#1b1b18] dark:text-[#A1A09A]">Bilang</span>
                                    <span className="text-lg font-bold text-[#1b1b18] dark:text-[#ffff]">Paulinian</span>
                                </div>
                            </div>
                        </div>
                        <button
                            onClick={toggleTheme}
                            className="rounded-full p-2 hover:bg-gray-100 dark:hover:bg-gray-800"
                            aria-label="Toggle theme"
                        >
                            {appearance === 'dark' ? (
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-[#EDEDEC]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            ) : (
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-[#1b1b18]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            )}
                        </button>
                    </nav>
                </header>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col items-center space-y-8 lg:max-w-4xl">
                        <div className="w-full max-w-md rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                            <h2 className="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">Voter Login</h2>
                            {flash?.error && (
                                <div className="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900">
                                    <p className="text-sm text-red-700 dark:text-red-200">{flash.error}</p>
                                </div>
                            )}
                            {flash?.success && (
                                <div className="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900">
                                    <p className="text-sm text-green-700 dark:text-green-200">{flash.success}</p>
                                </div>
                            )}
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div>
                                    <label htmlFor="code" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Student ID Number
                                    </label>
                                    <input
                                        type="text"
                                        id="code"
                                        name="code"
                                        value={data.code}
                                        onChange={handleCodeChange}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        placeholder="Enter your ID number"
                                        required
                                    />
                                    {errors.code && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.code}</p>
                                    )}
                                </div>
                                <div>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                    >
                                        {processing ? 'Verifying...' : 'Proceed to Vote'}
                                    </button>
                                </div>
                            </form>

                            <div className="mt-6 text-center">
                                <a href={route('results.public')} className="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    View Live Election Results
                                </a>
                            </div>
                        </div>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
