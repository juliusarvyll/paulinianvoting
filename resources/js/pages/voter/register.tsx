import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

interface PageProps extends SharedData {
    courses: Array<{
        id: number;
        course_name: string;
    }>;
    flash?: {
        error?: string;
        success?: string;
    };
}

export default function Register() {
    const { courses, flash = {} } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        last_name: '',
        first_name: '',
        middle_name: '',
        sex: '',
        course_id: '',
        year_level: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('voter.register'), {
            onError: (errors) => {
                console.error(errors);
            },
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Voter Registration">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet" />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <div className="w-full max-w-md rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                    <h2 className="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">Voter Registration</h2>

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

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label htmlFor="code" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Student ID Number
                            </label>
                            <input
                                type="text"
                                id="code"
                                value={data.code}
                                onChange={e => setData('code', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            />
                            {errors.code && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.code}</p>}
                        </div>

                        <div>
                            <label htmlFor="last_name" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Last Name
                            </label>
                            <input
                                type="text"
                                id="last_name"
                                value={data.last_name}
                                onChange={e => setData('last_name', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            />
                            {errors.last_name && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.last_name}</p>}
                        </div>

                        <div>
                            <label htmlFor="first_name" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                First Name
                            </label>
                            <input
                                type="text"
                                id="first_name"
                                value={data.first_name}
                                onChange={e => setData('first_name', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            />
                            {errors.first_name && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.first_name}</p>}
                        </div>

                        <div>
                            <label htmlFor="middle_name" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Middle Name
                            </label>
                            <input
                                type="text"
                                id="middle_name"
                                value={data.middle_name}
                                onChange={e => setData('middle_name', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            />
                            {errors.middle_name && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.middle_name}</p>}
                        </div>

                        <div>
                            <label htmlFor="sex" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Sex
                            </label>
                            <select
                                id="sex"
                                value={data.sex}
                                onChange={e => setData('sex', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            >
                                <option value="">Select sex</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                            {errors.sex && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.sex}</p>}
                        </div>

                        <div>
                            <label htmlFor="course_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Course
                            </label>
                            <select
                                id="course_id"
                                value={data.course_id}
                                onChange={e => setData('course_id', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            >
                                <option value="">Select course</option>
                                {courses.map(course => (
                                    <option key={course.id} value={course.id}>
                                        {course.course_name}
                                    </option>
                                ))}
                            </select>
                            {errors.course_id && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.course_id}</p>}
                        </div>

                        <div>
                            <label htmlFor="year_level" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Year Level
                            </label>
                            <select
                                id="year_level"
                                value={data.year_level}
                                onChange={e => setData('year_level', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            >
                                <option value="">Select year level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            {errors.year_level && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.year_level}</p>}
                        </div>

                        <div className="flex items-center justify-between">
                            <Link
                                href={route('welcome')}
                                className="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                Back to Login
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {processing ? 'Registering...' : 'Register'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
