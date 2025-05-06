import { Head } from '@inertiajs/react';
import { useAppearance } from '@/hooks/use-appearance';
import { useState, useEffect } from 'react';
import axios from 'axios';

interface Candidate {
    id: number;
    voter: {
        id: number;
        first_name: string;
        last_name: string;
        middle_name: string;
        name: string;
    };
    votes_count: number;
    slogan: string;
    photo_path: string | null;
}

interface Position {
    id: number;
    name: string;
    max_winners: number;
    level: 'university' | 'department' | 'course' | 'year_level';
    candidates: Candidate[];
}

interface Props {
    election: {
        id: number;
        name: string;
        start_at: string;
        end_at: string;
    };
    positions: {
        university: Position[];
        department: Position[];
        course: Position[];
        year_level: Position[];
    };
    initialTotalVoters: number;
    initialVotersTurnout: number;
}

export default function ResultsIndex({ election, positions: initialPositions, initialTotalVoters, initialVotersTurnout }: Props) {
    const { appearance, updateAppearance } = useAppearance();
    const [positions, setPositions] = useState(initialPositions);
    const [totalVoters, setTotalVoters] = useState(initialTotalVoters);
    const [votersTurnout, setVotersTurnout] = useState(initialVotersTurnout);
    const [loading, setLoading] = useState(false);
    const [lastUpdated, setLastUpdated] = useState(new Date());
    const [autoRefresh, setAutoRefresh] = useState(true);

    const toggleTheme = () => {
        updateAppearance(appearance === 'dark' ? 'light' : 'dark');
    };

    const refreshResults = async () => {
        try {
            setLoading(true);
            const response = await axios.get(route('results.data'));
            setPositions(response.data.positions);
            setTotalVoters(response.data.totalVoters);
            setVotersTurnout(response.data.votersTurnout);
            setLastUpdated(new Date());
        } catch (error) {
            console.error('Error fetching results:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        let intervalId: NodeJS.Timeout | null = null;

        if (autoRefresh) {
            intervalId = setInterval(() => {
                refreshResults();
            }, 10000); // Refresh every 10 seconds
        }

        return () => {
            if (intervalId) clearInterval(intervalId);
        };
    }, [autoRefresh]);

    const calculatePercentage = (votes: number) => {
        if (votersTurnout === 0) return 0;
        return Math.round((votes / votersTurnout) * 100);
    };

    const renderCandidateResults = (candidate: Candidate, position: Position) => {
        const percentage = calculatePercentage(candidate.votes_count);

        return (
            <div key={candidate.id} className="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div className="flex items-start gap-4">
                    <div className="h-16 w-16 flex-shrink-0 overflow-hidden rounded-full">
                        {candidate.photo_path ? (
                            <img
                                src={candidate.photo_path}
                                alt={candidate.voter.name}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center bg-gray-200 dark:bg-gray-700">
                                <span className="text-2xl font-bold text-gray-500 dark:text-gray-400">
                                    {candidate.voter.first_name[0]}
                                    {candidate.voter.last_name[0]}
                                </span>
                            </div>
                        )}
                    </div>
                    <div className="flex-1">
                        <h4 className="font-medium text-gray-900 dark:text-white">
                            {candidate.voter.name}
                        </h4>
                        {candidate.slogan && (
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{candidate.slogan}</p>
                        )}
                        <div className="mt-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {candidate.votes_count} votes
                                </span>
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {percentage}%
                                </span>
                            </div>
                            <div className="mt-1 h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    className="h-2.5 rounded-full bg-indigo-600 dark:bg-indigo-500"
                                    style={{ width: `${percentage}%` }}
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const renderPositionSection = (title: string, positionList: Position[]) => (
        <div className="mb-8 rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
            <h3 className="mb-6 text-xl font-semibold text-gray-900 dark:text-white">{title}</h3>
            <div className="space-y-8">
                {positionList.map((position) => (
                    <div key={position.id} className="space-y-4">
                        <h4 className="border-b border-gray-200 pb-2 text-lg font-medium text-gray-900 dark:border-gray-700 dark:text-white">
                            {position.name}
                            <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                (Top {position.max_winners})
                            </span>
                        </h4>
                        <div>
                            {position.candidates
                                .sort((a, b) => b.votes_count - a.votes_count)
                                .map((candidate) => renderCandidateResults(candidate, position))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );

    return (
        <>
            <Head title="Election Results" />
            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
                {/* Header */}
                <header className="border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg">
                                <img src="/images/spup-logo.png" alt="SPUP Logo" />
                            </div>
                            <div>
                                <h1 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    {election.name} - Results
                                </h1>
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    Last updated: {lastUpdated.toLocaleTimeString()}
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            <label className="inline-flex cursor-pointer items-center">
                                <input
                                    type="checkbox"
                                    checked={autoRefresh}
                                    onChange={() => setAutoRefresh(!autoRefresh)}
                                    className="peer sr-only"
                                />
                                <div className="peer relative h-6 w-11 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-indigo-800"></div>
                                <span className="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Auto Refresh</span>
                            </label>
                            <button
                                onClick={refreshResults}
                                disabled={loading}
                                className="flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                {loading ? (
                                    <>
                                        <svg className="mr-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Refreshing...
                                    </>
                                ) : (
                                    <>
                                        <svg className="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Refresh
                                    </>
                                )}
                            </button>
                            <button
                                onClick={toggleTheme}
                                className="rounded-full p-2 hover:bg-gray-100 dark:hover:bg-gray-700"
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
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="flex-1 px-4 py-8">
                    <div className="mx-auto max-w-7xl">
                        {/* Stats Cards */}
                        <div className="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
                                <h3 className="text-lg font-medium text-gray-600 dark:text-gray-400">Total Eligible Voters</h3>
                                <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{totalVoters}</p>
                            </div>
                            <div className="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
                                <h3 className="text-lg font-medium text-gray-600 dark:text-gray-400">Votes Cast</h3>
                                <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{votersTurnout}</p>
                            </div>
                            <div className="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
                                <h3 className="text-lg font-medium text-gray-600 dark:text-gray-400">Voter Turnout</h3>
                                <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                    {totalVoters > 0 ? Math.round((votersTurnout / totalVoters) * 100) : 0}%
                                </p>
                                <div className="mt-2 h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        className="h-2.5 rounded-full bg-green-600 dark:bg-green-500"
                                        style={{ width: `${totalVoters > 0 ? Math.round((votersTurnout / totalVoters) * 100) : 0}%` }}
                                    ></div>
                                </div>
                            </div>
                        </div>

                        {/* Results Sections */}
                        {renderPositionSection('University Wide Positions', positions.university)}
                        {renderPositionSection('Department Wide Positions', positions.department)}
                        {renderPositionSection('Course Wide Positions', positions.course)}
                        {renderPositionSection('Year Level Positions', positions.year_level)}
                    </div>
                </main>
            </div>
        </>
    );
}
